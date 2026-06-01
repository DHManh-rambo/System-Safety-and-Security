@extends('customer.layouts.customer')

@section('title', '🛒 Giỏ Hàng – Hoa Tươi Shop')
@section('page-id', 'gio-hang')

@section('head-styles')
<link rel="stylesheet" href="{{ asset('css/Customer/GioHang.css') }}">
@endsection

@section('content')
<div class="cart-page">

    <div class="cart-heading">🛒 Giỏ Hàng Của Bạn</div>

    @if(session('error'))
        <div class="alert-cart error">❌ {{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert-cart success">✅ {{ session('success') }}</div>
    @endif

    @if(empty($gioHang))
    {{-- EMPTY STATE --}}
    <div class="cart-table-wrap">
        <div class="cart-empty">
            <div class="cart-empty-icon">🛒</div>
            <h3>Giỏ hàng của bạn đang trống</h3>
            <p>Hãy chọn những bó hoa yêu thích và thêm vào giỏ nhé!</p>
            <a href="{{ route('customer.dashboard') }}" class="btn-shop">🌸 Tiếp tục mua sắm</a>
        </div>
    </div>
    @else
    {{-- CART LAYOUT --}}
    <div class="cart-layout">

        {{-- LEFT: Bảng sản phẩm --}}
        <div class="cart-table-wrap">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Sản Phẩm</th>
                        <th>Giá</th>
                        <th>Số Lượng</th>
                        <th>Tạm Tính</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cartTableBody">
                    @foreach($gioHang as $key => $item)
                    <tr id="row-{{ $key }}">
                        <td>
                            <div class="cart-product-cell">
                                @if($item['hinh_anh'])
                                    <img src="{{ asset($item['hinh_anh']) }}"
                                         alt="{{ $item['ten_san_pham'] }}"
                                         class="cart-product-img"
                                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                    <div class="cart-product-placeholder" style="display:none;">🌸</div>
                                @else
                                    <div class="cart-product-placeholder">🌸</div>
                                @endif
                                <div>
                                    <div class="cart-product-name">{{ $item['ten_san_pham'] }}</div>
                                    <div class="cart-product-lot">Lô #{{ $item['ma_chi_tiet_nhap'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="cart-price">{{ number_format($item['gia_ban'], 0, ',', '.') }}₫</td>
                        <td>
                            <div class="cart-qty-wrap">
                                <button class="cart-qty-btn"
                                    onclick="changeQty('{{ $key }}', {{ $item['so_luong'] - 1 }}, {{ $item['so_luong_con_lai'] }})">−</button>
                                <input class="cart-qty-input"
                                       type="number"
                                       value="{{ $item['so_luong'] }}"
                                       min="1"
                                       max="{{ $item['so_luong_con_lai'] }}"
                                       onchange="changeQty('{{ $key }}', parseInt(this.value), {{ $item['so_luong_con_lai'] }})"
                                       id="qty-{{ $key }}">
                                <button class="cart-qty-btn"
                                    onclick="changeQty('{{ $key }}', {{ $item['so_luong'] + 1 }}, {{ $item['so_luong_con_lai'] }})">+</button>
                            </div>
                        </td>
                        <td class="cart-subtotal" id="sub-{{ $key }}">
                            {{ number_format($item['gia_ban'] * $item['so_luong'], 0, ',', '.') }}₫
                        </td>
                        <td>
                            <button class="cart-remove-btn" onclick="removeItem('{{ $key }}')" title="Xóa">✕</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="padding: 16px 20px; border-top: 1px solid #fce7f3; display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="{{ route('customer.dashboard') }}"
                   style="display:inline-flex; align-items:center; gap:6px; color:#be185d; font-size:.88rem; font-weight:600; text-decoration:none;">
                    ← Tiếp tục xem sản phẩm
                </a>
            </div>
        </div>

        {{-- RIGHT: Tóm tắt đơn hàng --}}
        <div class="cart-summary-panel">
            <h3>💳 Tổng Cộng Giỏ Hàng</h3>

            <div class="summary-row">
                <span>Tạm tính</span>
                <span class="val" id="summarySubtotal">
                    {{ number_format(collect($gioHang)->sum(fn($i) => $i['gia_ban'] * $i['so_luong']), 0, ',', '.') }}₫
                </span>
            </div>
            <div class="summary-row">
                <span>Vận chuyển</span>
                <span class="ship-free">Miễn phí</span>
            </div>

            {{-- Điểm giảm giá --}}
            @auth
            @if($user && $user->khachHang)
            <div class="points-section">
                <div class="points-header">
                    <span class="points-label">🎁 Dùng điểm tích lũy</span>
                    <span class="points-avail">Bạn có: <strong>{{ number_format($user->khachHang->diem_tich_luy) }}</strong> điểm</span>
                </div>

                <div id="pointsInputArea">
                    <div class="points-input-row">
                        <input class="points-input" type="number" id="pointsInput"
                               placeholder="Nhập số điểm muốn dùng"
                               min="0" max="{{ $user->khachHang->diem_tich_luy }}">
                        <button class="points-apply-btn" onclick="applyPoints()">Áp dụng</button>
                    </div>
                    <div class="points-note">1 điểm = 1,000₫ &nbsp;|&nbsp; Tối đa {{ number_format($user->khachHang->diem_tich_luy) }} điểm</div>
                </div>

                <div id="pointsApplied" style="display:none;">
                    <div class="points-applied">
                        ✅ Đang dùng <strong id="diemDung">0</strong> điểm (−<span id="giamHienThi">0</span>₫)
                        <button class="points-cancel-btn" onclick="cancelPoints()">✕</button>
                    </div>
                </div>
            </div>
            @endif
            @endauth

            <div class="summary-row" id="discountRow" style="{{ $diemSuDung > 0 ? '' : 'display:none;' }}">
                <span>Giảm giá (điểm)</span>
                <span class="discount-val" id="summaryDiscount">
                    −{{ number_format($diemSuDung * 1000, 0, ',', '.') }}₫
                </span>
            </div>

            <div class="summary-row total">
                <span><strong>Tổng</strong></span>
                <span class="val" id="summaryTotal">
                    @php
                        $tongTienGoc = collect($gioHang)->sum(fn($i) => $i['gia_ban'] * $i['so_luong']);
                        $giamGia     = $diemSuDung * 1000;
                        $tongCuoi    = max(0, $tongTienGoc - $giamGia);
                    @endphp
                    {{ number_format($tongCuoi, 0, ',', '.') }}₫
                </span>
            </div>

            @auth
                <a href="{{ route('customer.thanh-toan') }}" class="btn-checkout">
                    💳 Tiến Hành Thanh Toán
                </a>
            @else
                <a href="{{ route('login') }}" class="btn-checkout">
                    🔑 Đăng nhập để thanh toán
                </a>
            @endauth

            <a href="{{ route('customer.dashboard') }}" class="btn-continue">
                ← Tiếp tục mua sắm
            </a>
        </div>

    </div>
    @endif

</div>
@endsection

@section('scripts')
<script>
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const fmt  = v => new Intl.NumberFormat('vi-VN').format(v) + '₫';

    let diemSuDung = {{ $diemSuDung }};

    // Giá của từng item (để tính lại subtotal phía client)
    const prices = {
        @foreach($gioHang as $key => $item)
        '{{ $key }}': {{ $item['gia_ban'] }},
        @endforeach
    };
    let quantities = {
        @foreach($gioHang as $key => $item)
        '{{ $key }}': {{ $item['so_luong'] }},
        @endforeach
    };

    function recalcSummary() {
        let subtotal = 0;
        Object.keys(quantities).forEach(k => {
            subtotal += (prices[k] || 0) * (quantities[k] || 0);
        });
        const giam  = diemSuDung * 1000;
        const total = Math.max(0, subtotal - giam);

        document.getElementById('summarySubtotal').textContent = fmt(subtotal);
        document.getElementById('summaryTotal').textContent    = fmt(total);

        const discRow = document.getElementById('discountRow');
        if (discRow) {
            discRow.style.display = diemSuDung > 0 ? '' : 'none';
            document.getElementById('summaryDiscount').textContent = '−' + fmt(giam);
        }
    }

    async function changeQty(key, newQty, maxQty) {
        newQty = Math.max(1, Math.min(newQty, maxQty));

        const res  = await fetch('/customer/gio-hang/update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ key, so_luong: newQty }),
        });
        const data = await res.json();
        if (data.success) {
            quantities[key] = newQty;
            document.getElementById('qty-' + key).value = newQty;
            document.getElementById('sub-' + key).textContent =
                fmt((prices[key] || 0) * newQty);
            recalcSummary();
            updateCartBadge(data.so_san_pham);
            diemSuDung = 0;
            resetPointsUI();
        }
    }

    async function removeItem(key) {
        const row = document.getElementById('row-' + key);
        row.style.opacity = '.4';

        const res  = await fetch('/customer/gio-hang/remove', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ key }),
        });
        const data = await res.json();
        if (data.success) {
            row.remove();
            delete quantities[key];
            delete prices[key];
            recalcSummary();
            updateCartBadge(data.so_san_pham);

            if (Object.keys(quantities).length === 0) {
                location.reload();
            }
        }
    }

    async function applyPoints() {
        const diem = parseInt(document.getElementById('pointsInput').value) || 0;
        if (diem <= 0) return;

        const res  = await fetch('/customer/gio-hang/apply-points', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ diem }),
        });
        const data = await res.json();
        if (data.success) {
            diemSuDung = data.diem_su_dung;
            document.getElementById('pointsInputArea').style.display = 'none';
            document.getElementById('pointsApplied').style.display   = '';
            document.getElementById('diemDung').textContent           = new Intl.NumberFormat('vi-VN').format(diemSuDung);
            document.getElementById('giamHienThi').textContent        = new Intl.NumberFormat('vi-VN').format(data.giam_gia);
            recalcSummary();
            showToast('✅ Áp dụng ' + diemSuDung + ' điểm thành công!');
        }
    }

    function cancelPoints() {
        diemSuDung = 0;
        resetPointsUI();
        recalcSummary();

        fetch('/customer/gio-hang/apply-points', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ diem: 0 }),
        });
    }

    function resetPointsUI() {
        const inp = document.getElementById('pointsInputArea');
        const app = document.getElementById('pointsApplied');
        if (inp) inp.style.display = '';
        if (app) app.style.display = 'none';
        const pi = document.getElementById('pointsInput');
        if (pi) pi.value = '';
    }

    @if($diemSuDung > 0)
    window.addEventListener('DOMContentLoaded', () => {
        const area = document.getElementById('pointsInputArea');
        const appl = document.getElementById('pointsApplied');
        if (area) area.style.display = 'none';
        if (appl) {
            appl.style.display = '';
            document.getElementById('diemDung').textContent    = '{{ number_format($diemSuDung) }}';
            document.getElementById('giamHienThi').textContent = '{{ number_format($diemSuDung * 1000) }}';
        }
    });
    @endif
</script>
@endsection