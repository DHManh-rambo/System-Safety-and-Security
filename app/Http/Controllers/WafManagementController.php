<?php

namespace App\Http\Controllers;

use App\Models\WafSetting;
use App\Models\AccessLog;
use Illuminate\Http\Request;

class WafManagementController extends Controller
{
    public function index(Request $request)
    {
        $firewallEnabled = WafSetting::where('key', 'firewall_enabled')->value('value') ?? false;

        // ── Thống kê BAC hôm nay ────────────────────────────
        // Tất cả chỉ lọc attack_type = 'BAC', không có SQLi/XSS
        $stats = [
            // Tổng số lần phát hiện BAC trong ngày (cả chặn lẫn cảnh báo)
            'bac_total_today' => AccessLog::today()->byAttackType('BAC')->count(),

            // Số lần BAC bị chặn thật sự (WAF bật, was_blocked = true)
            'bac_blocked'     => AccessLog::today()->byAttackType('BAC')->blocked()->count(),

            // Số lần BAC chỉ ghi cảnh báo (WAF tắt, was_blocked = false)
            'bac_warned'      => AccessLog::today()->byAttackType('BAC')
                                    ->where('was_blocked', false)->count(),

            // Số lần BAC mức nguy hiểm cao (route nhạy cảm hoặc dò quyền nhiều lần)
            'bac_high'        => AccessLog::today()->byAttackType('BAC')->highThreat()->count(),
        ];

        // ── Filter log ──────────────────────────────────────
        // Mặc định chỉ hiển thị log BAC, sắp xếp mới nhất trước
        $query = AccessLog::where('attack_type', 'BAC')
                          ->orderBy('attempted_at', 'desc');

        if ($request->filled('threat_level')) {
            $query->where('threat_level', $request->threat_level);
        }
        if ($request->filled('was_blocked')) {
            $query->where('was_blocked', $request->was_blocked === '1');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('attempted_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('attempted_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('ip', 'like', "%$s%")
                  ->orWhere('url', 'like', "%$s%")
                  ->orWhere('vai_tro', 'like', "%$s%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('waf-management', compact('firewallEnabled', 'stats', 'logs'));
    }

    // ── Bật / Tắt tường lửa ─────────────────────────────────
    // [TO-DO PHÒNG THỦ] Thêm Cache::forget('waf_enabled') ở đây
    // để middleware đọc lại giá trị mới ngay, không phải chờ hết TTL cache.
    public function toggleFirewall()
    {
        $setting = WafSetting::firstOrCreate(
            ['key' => 'firewall_enabled'],
            ['value' => false]
        );
        $setting->value = !$setting->value;
        $setting->save();

        $status = $setting->value ? 'BẬT' : 'TẮT';
        return back()->with('success', "Tường lửa đã được $status.");
    }

    // ── Xóa log cũ ──────────────────────────────────────────
    // Chỉ xóa log BAC cũ hơn số ngày chỉ định.
    // Tránh bảng access_logs phình to vô hạn theo thời gian.
    public function clearLogs(Request $request)
    {
        $request->validate([
            'older_than_days' => 'nullable|integer|min:1|max:365',
        ]);

        $days    = $request->older_than_days ?? 30;
        $deleted = AccessLog::where('attack_type', 'BAC')
                            ->where('attempted_at', '<', now()->subDays($days))
                            ->delete();

        return back()->with('success', "Đã xóa {$deleted} log BAC cũ hơn {$days} ngày.");
    }
}