<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\WafSetting;
use App\Models\AccessLog;

class WafMiddleware
{
    // ─────────────────────────────────────────────────────────────────────────
    // CẤU HÌNH
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Danh sách các từ khóa trong URL được coi là "nhạy cảm".
     * Khi BAC xảy ra trên các route này → threat_level = 'high'.
     * Khi BAC xảy ra trên route bình thường → threat_level = 'medium'.
     */
    private const SENSITIVE_ROUTES = [
        'nguoi-dung',   // quản lý người dùng
        'nhan-vien',    // quản lý nhân viên + tra tiền COD
        'bao-cao',      // báo cáo tài chính
        'waf',          // dashboard WAF
        'phieu-nhap',   // nhập kho
    ];

    /**
     * Sau bao nhiêu lần BAC bị bắt từ cùng 1 IP/user
     * trong cửa sổ thời gian → coi là đang dò quyền có hệ thống
     * và nâng threat_level lên 'high' dù route không nhạy cảm.
     */
    private const PROBE_THRESHOLD = 3;

    /**
     * Cửa sổ thời gian tính số lần dò quyền (giây).
     * Mặc định 5 phút.
     */
    private const PROBE_WINDOW = 300;

    /**
     * Sau bao nhiêu lần BAC bị bắt → khóa IP hoàn toàn.
     */
    private const BLOCK_THRESHOLD = 10;

    /**
     * Thời gian khóa IP (giây). Mặc định 15 phút.
     */
    private const BLOCK_DURATION = 900;

    // ─────────────────────────────────────────────────────────────────────────
    // ENTRY POINT
    // ─────────────────────────────────────────────────────────────────────────

    public function handle(Request $request, Closure $next, ...$requiredRoles): mixed
    {
        // [TO-DO PHÒNG THỦ] ── Đọc trạng thái bật/tắt WAF ──────────────────
        // Bạn tự implement phần này.
        // Gợi ý: đọc WafSetting::where('key','firewall_enabled')->value('value')
        // Nếu false → gọi $this->handleWafOff(...) rồi return $next($request)
        // Nếu true  → chạy tiếp các bước bên dưới
        // ────────────────────────────────────────────────────────────────────
        $firewallEnabled = WafSetting::where('key', 'firewall_enabled')->value('value') ?? false;

        if (!$firewallEnabled) {
            return $this->handleWafOff($request, $next, $requiredRoles);
        }

        // ── Bước 1: Kiểm tra IP đã bị khóa chưa ────────────────────────────
        if ($this->isIpLocked($request)) {
            $this->writeLog($request, $requiredRoles,
                blocked: true,
                threatLevel: 'high',
                blockedReason: 'IP bị khóa do dò quyền quá nhiều lần (BAC repeated probing)',
                attackType: 'BAC'
            );
            abort(429, 'IP của bạn tạm thời bị khóa. Vui lòng thử lại sau.');
        }

        // ── Bước 2: Yêu cầu đăng nhập ───────────────────────────────────────
        if (!Auth::check()) {
            // Guest cố truy cập route cần auth → BAC mức low
            $this->writeLog($request, $requiredRoles,
                blocked: true,
                threatLevel: 'low',
                blockedReason: 'Guest truy cập route yêu cầu xác thực',
                attackType: 'BAC'
            );
            return redirect()->route('login');
        }

        // ── Bước 3: Kiểm tra vai trò (BAC dọc) ──────────────────────────────
        $userRole = Auth::user()->vai_tro;

        if (!in_array($userRole, $requiredRoles)) {
            // Xác định threat level dựa trên route và lịch sử dò quyền
            $threatLevel = $this->calcBacThreatLevel($request, $userRole);

            // Tăng bộ đếm dò quyền cho IP này
            $this->incrementProbeCounter($request);

            // Ghi log
            $this->writeLog($request, $requiredRoles,
                blocked: true,
                threatLevel: $threatLevel,
                blockedReason: "BAC (Vertical): role '{$userRole}' cố truy cập route dành cho [" . implode(', ', $requiredRoles) . "]",
                attackType: 'BAC'
            );

            abort(403, 'Truy cập bị từ chối.');
        }

        return $next($request);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // XỬ LÝ KHI WAF TẮT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Khi WAF tắt: không chặn request, nhưng VẪN ghi log cảnh báo
     * nếu phát hiện BAC. Giúp admin thấy nguy cơ dù chưa bật tường lửa.
     *
     * [TO-DO PHÒNG THỦ] Bạn có thể mở rộng hàm này:
     * - Gửi email cảnh báo cho admin khi phát hiện BAC lúc WAF tắt
     * - Tăng counter riêng để thống kê "số tấn công bị bỏ qua do WAF tắt"
     */
    private function handleWafOff(Request $request, Closure $next, array $requiredRoles): mixed
    {
        if (Auth::check() && !empty($requiredRoles)) {
            $userRole = Auth::user()->vai_tro;
            if (!in_array($userRole, $requiredRoles)) {
                $this->writeLog($request, $requiredRoles,
                    blocked: false,
                    threatLevel: $this->calcBacThreatLevel($request, $userRole),
                    blockedReason: "CẢNH BÁO (WAF tắt): role '{$userRole}' đang truy cập trái phép — không bị chặn vì WAF đang tắt",
                    attackType: 'BAC'
                );
            }
        }

        return $next($request);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TÍNH THREAT LEVEL CHO BAC
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Logic phân loại mức nguy hiểm BAC:
     *
     * HIGH  → route nhạy cảm (tài chính, user management, WAF)
     *       → HOẶC IP đã dò quyền >= PROBE_THRESHOLD lần trước đó
     *
     * MEDIUM → route bình thường, IP chưa có lịch sử dò quyền nhiều
     *
     * Bảng phân loại:
     * ┌─────────────────────────────────────────┬─────────────┐
     * │ Tình huống                              │ Threat Level│
     * ├─────────────────────────────────────────┼─────────────┤
     * │ Guest truy cập route cần auth           │ low         │
     * │ User sai role, route bình thường        │ medium      │
     * │ User sai role, route nhạy cảm           │ high        │
     * │ User sai role, đã dò ≥ PROBE_THRESHOLD  │ high        │
     * │ IP đã bị đánh dấu locked                │ high        │
     * └─────────────────────────────────────────┴─────────────┘
     */
    private function calcBacThreatLevel(Request $request, string $userRole): string
    {
        $url = strtolower($request->path());

        // Kiểm tra route nhạy cảm
        foreach (self::SENSITIVE_ROUTES as $keyword) {
            if (str_contains($url, $keyword)) {
                return 'high';
            }
        }

        // Kiểm tra lịch sử dò quyền
        $probeCount = Cache::get($this->probeKey($request), 0);
        if ($probeCount >= self::PROBE_THRESHOLD) {
            return 'high';
        }

        return 'medium';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // QUẢN LÝ BỘ ĐẾM DÒ QUYỀN (PROBE COUNTER)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Cache key để đếm số lần BAC của một IP.
     * Dùng IP thay vì user_id để bắt cả trường hợp
     * kẻ tấn công dùng nhiều tài khoản từ cùng một máy.
     */
    private function probeKey(Request $request): string
    {
        return 'waf_bac_probe:' . $request->ip();
    }

    /**
     * Cache key để đánh dấu IP bị khóa hoàn toàn.
     */
    private function lockKey(Request $request): string
    {
        return 'waf_bac_locked:' . $request->ip();
    }

    /**
     * Tăng bộ đếm dò quyền.
     * Khi vượt BLOCK_THRESHOLD → khóa IP.
     *
     * [TO-DO PHÒNG THỦ] Bạn có thể thêm:
     * - Ghi vào DB riêng danh sách IP bị khóa (để admin quản lý qua UI)
     * - Gửi alert realtime khi IP bị khóa
     */
    private function incrementProbeCounter(Request $request): void
    {
        $key   = $this->probeKey($request);
        $count = Cache::get($key, 0) + 1;

        Cache::put($key, $count, self::PROBE_WINDOW);

        // Nếu vượt ngưỡng → khóa IP
        if ($count >= self::BLOCK_THRESHOLD) {
            Cache::put($this->lockKey($request), true, self::BLOCK_DURATION);
        }
    }

    /**
     * Kiểm tra IP có đang bị khóa không.
     */
    private function isIpLocked(Request $request): bool
    {
        return Cache::has($this->lockKey($request));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GHI LOG
    // ─────────────────────────────────────────────────────────────────────────

    private function writeLog(
        Request $request,
        array   $requiredRoles,
        bool    $blocked,
        string  $threatLevel,
        string  $blockedReason,
        string  $attackType
    ): void {
        AccessLog::create([
            'user_id'         => Auth::check() ? Auth::user()->ma_nguoi_dung : null,
            'vai_tro'         => Auth::check() ? Auth::user()->vai_tro : 'GUEST',
            'ip'              => $request->ip(),
            'url'             => $request->fullUrl(),
            'method'          => $request->method(),
            'required_roles'  => $requiredRoles,
            'attempted_at'    => now(),
            'was_blocked'     => $blocked,
            'threat_level'    => $threatLevel,
            'blocked_reason'  => $blockedReason,
            'user_agent'      => $request->userAgent(),
            'request_payload' => null, // Không cần lưu payload cho BAC
            'attack_type'     => $attackType,
        ]);
    }
}