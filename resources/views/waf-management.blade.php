<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quản lý WAF — Broken Access Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg:      #f5f6f8;
            --card-bg: #ffffff;
            --border:  #e2e5ea;
            --text:    #2c3340;
            --muted:   #6b7280;
            --accent:  #2563eb;
            --danger:  #dc2626;
            --warning: #d97706;
            --success: #16a34a;
            --radius:  8px;
        }

        * { box-sizing: border-box; }

        body {
            background: var(--bg);
            font-family: 'Segoe UI', system-ui, sans-serif;
            font-size: 14px;
            color: var(--text);
            padding: 24px;
            line-height: 1.5;
        }

        .container { max-width: 1360px; margin: 0 auto; }

        /* ── Header ───────────────────────────────────────── */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .page-title { font-size: 20px; font-weight: 700; margin: 0; }
        .page-subtitle { font-size: 12px; color: var(--muted); margin-top: 2px; }
        .btn-back {
            font-size: 13px; color: var(--muted); text-decoration: none;
            border: 1px solid var(--border); padding: 6px 14px;
            border-radius: var(--radius); background: var(--card-bg);
        }
        .btn-back:hover { background: #f0f1f3; color: var(--text); }

        /* ── Alert ────────────────────────────────────────── */
        .alert {
            padding: 10px 14px; border-radius: var(--radius);
            font-size: 13px; margin-bottom: 16px; border: 1px solid transparent;
        }
        .alert-success { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
        .alert-danger  { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }

        /* ── Card ─────────────────────────────────────────── */
        .card {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: var(--radius); margin-bottom: 20px;
        }
        .card-header {
            padding: 12px 16px; border-bottom: 1px solid var(--border);
            font-weight: 600; font-size: 13px; color: var(--muted);
            text-transform: uppercase; letter-spacing: .04em;
            background: #fafbfc; border-radius: var(--radius) var(--radius) 0 0;
            display: flex; justify-content: space-between; align-items: center;
        }
        .card-body { padding: 16px; }

        /* ── Trạng thái tường lửa ─────────────────────────── */
        .fw-status { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .fw-status-text { flex: 1; font-size: 13px; }

        .waf-on-banner {
            background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d;
            padding: 10px 14px; border-radius: var(--radius); font-size: 13px;
        }
        .waf-off-banner {
            background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c;
            padding: 10px 14px; border-radius: var(--radius); font-size: 13px;
        }

        /* [TO-DO PHÒNG THỦ] ─────────────────────────────────
           Nút toggle bật/tắt WAF nằm ở đây.
           Tìm class btn-toggle-on / btn-toggle-off bên dưới.
           Form POST đến route('admin.waf.toggle').
           Bạn implement logic controller trong toggleFirewall().
        ─────────────────────────────────────────────────── */
        .btn-toggle-on {
            background: var(--danger); color: #fff; border: none;
            border-radius: var(--radius); padding: 7px 20px;
            font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;
        }
        .btn-toggle-off {
            background: var(--accent); color: #fff; border: none;
            border-radius: var(--radius); padding: 7px 20px;
            font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;
        }
        .btn-toggle-on:hover  { background: #b91c1c; }
        .btn-toggle-off:hover { background: #1d4ed8; }

        /* ── Stat grid BAC ────────────────────────────────── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .stat-item {
            background: var(--card-bg); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 16px;
        }
        .stat-item.primary { border-left: 3px solid var(--accent); }
        .stat-item.danger  { border-left: 3px solid var(--danger); }
        .stat-item.warning { border-left: 3px solid var(--warning); }
        .stat-item.success { border-left: 3px solid var(--success); }

        .stat-value { font-size: 26px; font-weight: 700; line-height: 1; color: var(--text); }
        .stat-label { font-size: 12px; color: var(--muted); margin-top: 5px; }
        .stat-item.primary .stat-value { color: var(--accent); }
        .stat-item.danger  .stat-value { color: var(--danger); }
        .stat-item.warning .stat-value { color: var(--warning); }
        .stat-item.success .stat-value { color: var(--success); }

        /* ── Filter bar ───────────────────────────────────── */
        .filter-bar {
            background: #fafbfc; border: 1px solid var(--border);
            border-radius: var(--radius); padding: 12px 14px; margin-bottom: 14px;
        }
        .filter-bar .form-label {
            font-size: 11px; color: var(--muted); margin-bottom: 3px;
            font-weight: 600; text-transform: uppercase; letter-spacing: .03em;
            display: block;
        }
        .form-select, .form-control {
            font-size: 13px; border-color: var(--border); border-radius: 6px;
        }
        .form-select:focus, .form-control:focus {
            border-color: var(--accent); box-shadow: 0 0 0 2px rgba(37,99,235,.1);
        }
        .btn-search {
            background: var(--accent); color: #fff; border: none;
            border-radius: 6px; padding: 5px 14px;
            font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .btn-search:hover { background: #1d4ed8; }
        .btn-reset {
            background: transparent; color: var(--muted);
            border: 1px solid var(--border); border-radius: 6px;
            padding: 5px 12px; font-size: 13px; cursor: pointer;
            text-decoration: none;
        }
        .btn-reset:hover { background: #f0f1f3; }

        /* ── Table ────────────────────────────────────────── */
        .table { margin-bottom: 0; font-size: 13px; }
        .table thead th {
            background: #fafbfc; font-weight: 600; font-size: 12px;
            color: var(--muted); text-transform: uppercase; letter-spacing: .03em;
            border-color: var(--border); padding: 9px 12px; white-space: nowrap;
        }
        .table tbody td { border-color: #f0f1f3; padding: 9px 12px; vertical-align: middle; }
        .table tbody tr:hover { background: #f9fafb; }

        /* Highlight dòng theo threat level */
        tr.threat-high   { background: #fff8f8; }
        tr.threat-high td:first-child { border-left: 3px solid var(--danger); }
        tr.threat-medium { background: #fffdf5; }
        tr.threat-medium td:first-child { border-left: 3px solid var(--warning); }
        tr.threat-low td:first-child { border-left: 3px solid #d1d5db; }

        /* ── Badges ───────────────────────────────────────── */
        .badge {
            display: inline-block; padding: 2px 8px; border-radius: 4px;
            font-size: 11px; font-weight: 600; letter-spacing: .02em;
        }
        /* BAC subtypes */
        .badge-bac-vertical  { background: #eff6ff; color: var(--accent);  border: 1px solid #bfdbfe; }
        .badge-bac-probe     { background: #fff7ed; color: #c2410c;        border: 1px solid #fed7aa; }
        .badge-bac-guest     { background: #f8fafc; color: var(--muted);   border: 1px solid var(--border); }
        .badge-bac-locked    { display: none; }
        /* Threat level */
        .badge-high   { background: #fef2f2; color: var(--danger);  border: 1px solid #fecaca; }
        .badge-medium { background: #fffbeb; color: var(--warning); border: 1px solid #fde68a; }
        .badge-low    { background: #f0fdf4; color: var(--success); border: 1px solid #bbf7d0; }
        /* Status */
        .badge-blocked { background: #fef2f2; color: var(--danger);  border: 1px solid #fecaca; }
        .badge-warn    { background: #fffbeb; color: var(--warning); border: 1px solid #fde68a; }

        /* ── Misc ─────────────────────────────────────────── */
        .url-cell { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .reason-cell { max-width: 240px; font-size: 12px; color: var(--muted); }
        .ua-cell { max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--muted); font-size: 12px; }

        .table-footer {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 12px; font-size: 12px; color: var(--muted);
        }
        .btn-clear {
            background: transparent; color: var(--muted);
            border: 1px solid var(--border); border-radius: 6px;
            padding: 4px 12px; font-size: 12px; cursor: pointer;
        }
        .btn-clear:hover { background: #fef2f2; color: var(--danger); border-color: #fecaca; }
        .empty-state { text-align: center; padding: 48px 0; color: var(--muted); }
        .empty-state p { margin: 0; font-size: 13px; }
        code { font-size: 12px; background: #f1f5f9; padding: 1px 5px; border-radius: 3px; color: #334155; }

        /* ── Section title ────────────────────────────────── */
        .section-label {
            font-size: 11px; font-weight: 700; color: var(--muted);
            text-transform: uppercase; letter-spacing: .06em;
            margin-bottom: 10px; padding-bottom: 6px;
            border-bottom: 1px solid var(--border);
        }
    </style>
</head>
<body>
<div class="container">

    {{-- ── Header ────────────────────────────────────────────── --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">WAF — Broken Access Control</h1>
            <div class="page-subtitle">Phát hiện &amp; ngăn chặn truy cập trái phép theo vai trò</div>
        </div>
        <a href="{{ url('/admin/dashboard') }}" class="btn-back">← Về Dashboard</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    {{-- ── 1. TRẠNG THÁI TƯỜNG LỬA ────────────────────────────── --}}
    {{--
        [TO-DO PHÒNG THỦ]
        Đây là khu vực bật/tắt WAF.
        Form bên dưới POST đến route('admin.waf.toggle').
        Logic xử lý nằm trong WafManagementController@toggleFirewall().
        Bạn cần implement thêm: cache invalidation sau khi toggle
        để middleware đọc lại giá trị mới ngay lập tức.
    --}}
    <div class="card">
        <div class="card-header">
            <span>Trạng thái tường lửa</span>
            <span style="font-size:11px;font-weight:400">
                @if($firewallEnabled)
                    <span style="color:var(--success)">● ĐANG BẬT</span>
                @else
                    <span style="color:var(--danger)">● ĐANG TẮT</span>
                @endif
            </span>
        </div>
        <div class="card-body">
            <div class="fw-status">
                <div class="fw-status-text">
                    @if($firewallEnabled)
                        <div class="waf-on-banner">
                            <strong>Tường lửa đang bật.</strong>
                            Mọi request vi phạm vai trò đều bị chặn và ghi log ngay lập tức.
                            Hệ thống phân loại mức nguy hiểm tự động theo loại route và lịch sử dò quyền.
                        </div>
                    @else
                        <div class="waf-off-banner">
                            <strong>Tường lửa đang tắt.</strong>
                            Lỗ hổng Broken Access Control đang tồn tại — mọi user đã đăng nhập có thể truy cập bất kỳ route nào.
                            Hệ thống vẫn <em>ghi log cảnh báo</em> nhưng <strong>không chặn</strong>.
                        </div>
                    @endif
                </div>

                {{-- Nút toggle --}}
                <form method="POST" action="{{ route('admin.waf.toggle') }}">
                    @csrf
                    <button type="submit"
                            class="{{ $firewallEnabled ? 'btn-toggle-on' : 'btn-toggle-off' }}"
                            onclick="return confirm('{{ $firewallEnabled ? 'Tắt tường lửa? Hệ thống sẽ mất bảo vệ BAC!' : 'Bật tường lửa?' }}')">
                        {{ $firewallEnabled ? '⏹ Tắt tường lửa' : '▶ Bật tường lửa' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── 2. THỐNG KÊ BAC ─────────────────────────────────────── --}}
    {{--
        Các con số này được tính trong WafManagementController@index().
        Tất cả đều lọc attack_type = 'BAC' — không có SQLi/XSS ở đây.
        bac_blocked   = BAC bị chặn thật (was_blocked = true)
        bac_warned    = BAC chỉ cảnh báo, WAF tắt (was_blocked = false)
        bac_high      = BAC mức nguy hiểm cao (route nhạy cảm / dò nhiều lần)
        bac_probing   = IP đang có dấu hiệu dò quyền lặp lại
    --}}
    <div class="section-label">Thống kê hôm nay — Broken Access Control</div>
    <div class="stat-grid">
        <div class="stat-item primary">
            <div class="stat-value">{{ $stats['bac_total_today'] }}</div>
            <div class="stat-label">Tổng sự kiện BAC</div>
        </div>
        <div class="stat-item danger">
            <div class="stat-value">{{ $stats['bac_blocked'] }}</div>
            <div class="stat-label">Đã chặn</div>
        </div>
        <div class="stat-item warning">
            <div class="stat-value">{{ $stats['bac_warned'] }}</div>
            <div class="stat-label">Cảnh báo (WAF tắt)</div>
        </div>
        <div class="stat-item danger">
            <div class="stat-value">{{ $stats['bac_high'] }}</div>
            <div class="stat-label">Mức cao (High)</div>
        </div>
    </div>

    {{-- ── 3. LOG PHÁT HIỆN BAC ────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <span>Log phát hiện Broken Access Control</span>
            <form method="POST" action="{{ route('admin.waf.clear-logs') }}"
                  style="display:flex;gap:8px;align-items:center;">
                @csrf
                <input type="number" name="older_than_days"
                       class="form-control form-control-sm" style="width:72px"
                       value="30" min="1" max="365">
                <span style="font-size:12px;color:var(--muted)">ngày</span>
                <button class="btn-clear"
                        onclick="return confirm('Xóa log BAC cũ hơn số ngày đã nhập?')">
                    Xóa log cũ
                </button>
            </form>
        </div>
        <div class="card-body">

            {{-- Filter bar --}}
            <form method="GET" action="{{ route('admin.waf.index') }}" class="filter-bar">
                <div class="row g-2 align-items-end">

                    {{-- Mức nguy hiểm --}}
                    <div class="col-6 col-md-2">
                        <label class="form-label">Mức nguy hiểm</label>
                        <select name="threat_level" class="form-select form-select-sm">
                            <option value="">Tất cả</option>
                            <option value="high"   {{ request('threat_level') === 'high'   ? 'selected' : '' }}>High — Route nhạy cảm / Dò nhiều lần</option>
                            <option value="medium" {{ request('threat_level') === 'medium' ? 'selected' : '' }}>Medium — Sai vai trò, route thường</option>
                            <option value="low"    {{ request('threat_level') === 'low'    ? 'selected' : '' }}>Low — Guest chưa đăng nhập</option>
                        </select>
                    </div>

                    {{-- Trạng thái --}}
                    <div class="col-6 col-md-2">
                        <label class="form-label">Trạng thái</label>
                        <select name="was_blocked" class="form-select form-select-sm">
                            <option value="">Tất cả</option>
                            <option value="1" {{ request('was_blocked') === '1' ? 'selected' : '' }}>Đã chặn (WAF bật)</option>
                            <option value="0" {{ request('was_blocked') === '0' ? 'selected' : '' }}>Cảnh báo (WAF tắt)</option>
                        </select>
                    </div>

                    {{-- Từ ngày --}}
                    <div class="col-6 col-md-2">
                        <label class="form-label">Từ ngày</label>
                        <input type="date" name="date_from"
                               class="form-control form-control-sm"
                               value="{{ request('date_from') }}">
                    </div>

                    {{-- Đến ngày --}}
                    <div class="col-6 col-md-2">
                        <label class="form-label">Đến ngày</label>
                        <input type="date" name="date_to"
                               class="form-control form-control-sm"
                               value="{{ request('date_to') }}">
                    </div>

                    {{-- Tìm kiếm --}}
                    <div class="col-12 col-md-4">
                        <label class="form-label">Tìm IP / URL / Vai trò</label>
                        <div class="input-group input-group-sm" style="gap:6px">
                            <input type="text" name="search"
                                   class="form-control"
                                   value="{{ request('search') }}"
                                   placeholder="Nhập IP, URL hoặc vai trò...">
                            <button class="btn-search" type="submit">Tìm</button>
                            <a href="{{ route('admin.waf.index') }}" class="btn-reset">Reset</a>
                        </div>
                    </div>

                </div>
            </form>

            {{-- Bảng log --}}
            @if($logs->count())
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Vai trò kẻ tấn công</th>
                                <th>IP</th>
                                <th>Loại BAC</th>
                                <th>Mức nguy hiểm</th>
                                <th>Trạng thái</th>
                                <th>Lý do chi tiết</th>
                                <th>URL bị tấn công</th>
                                <th>Quyền yêu cầu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                            <tr class="threat-{{ $log->threat_level }}">

                                {{-- Thời gian --}}
                                <td style="white-space:nowrap;font-size:12px">
                                    {{ $log->attempted_at->format('d/m/Y') }}<br>
                                    <span style="color:var(--muted)">{{ $log->attempted_at->format('H:i:s') }}</span>
                                </td>

                                {{-- Vai trò kẻ tấn công --}}
                                <td>
                                    <div style="font-weight:600">{{ $log->vai_tro }}</div>
                                    @if($log->user_id)
                                        <div style="font-size:11px;color:var(--muted)">ID: {{ $log->user_id }}</div>
                                    @else
                                        <div style="font-size:11px;color:#9ca3af">Guest</div>
                                    @endif
                                </td>

                                {{-- IP --}}
                                <td><code>{{ $log->ip }}</code></td>

                                {{-- Loại BAC --}}
                                <td>
                                    @php
                                        $reason = strtolower($log->blocked_reason ?? '');
                                    @endphp
                                    @if(str_contains($reason, 'ip bị khóa') || str_contains($reason, 'locked'))
                                        <span class="badge badge-bac-locked">IP bị khóa</span>
                                    @elseif(str_contains($reason, 'dò quyền') || str_contains($reason, 'repeated') || str_contains($reason, 'probe'))
                                        <span class="badge badge-bac-probe">Dò quyền lặp lại</span>
                                    @elseif(str_contains($reason, 'guest') || $log->vai_tro === 'GUEST')
                                        <span class="badge badge-bac-guest">Guest truy cập</span>
                                    @else
                                        <span class="badge badge-bac-vertical">Sai vai trò</span>
                                    @endif
                                </td>

                                {{-- Mức nguy hiểm --}}
                                <td>
                                    @if($log->threat_level === 'high')
                                        <span class="badge badge-high">High</span>
                                    @elseif($log->threat_level === 'medium')
                                        <span class="badge badge-medium">Medium</span>
                                    @else
                                        <span class="badge badge-low">Low</span>
                                    @endif
                                </td>

                                {{-- Trạng thái chặn --}}
                                <td>
                                    @if($log->was_blocked)
                                        <span class="badge badge-blocked">Đã chặn</span>
                                    @else
                                        <span class="badge badge-warn">Cảnh báo</span>
                                    @endif
                                </td>

                                {{-- Lý do --}}
                                <td class="reason-cell" title="{{ $log->blocked_reason }}">
                                    {{ $log->blocked_reason }}
                                </td>

                                {{-- URL --}}
                                <td class="url-cell" title="{{ $log->url }}">
                                    <code style="font-size:11px">{{ $log->url }}</code>
                                </td>

                                {{-- Quyền yêu cầu --}}
                                <td style="font-size:12px;white-space:nowrap">
                                    @if(!empty($log->required_roles))
                                        @foreach($log->required_roles as $role)
                                            <span style="background:#f1f5f9;padding:1px 6px;border-radius:3px;font-size:11px">{{ $role }}</span>
                                        @endforeach
                                    @else
                                        <span style="color:#9ca3af">—</span>
                                    @endif
                                </td>

                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <span>
                        Hiển thị {{ $logs->firstItem() }}–{{ $logs->lastItem() }}
                        / {{ $logs->total() }} bản ghi BAC
                    </span>
                    {{ $logs->links() }}
                </div>

            @else
                <div class="empty-state">
                    <p>Không có log BAC nào phù hợp với bộ lọc hiện tại.</p>
                </div>
            @endif

        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>