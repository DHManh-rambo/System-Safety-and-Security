@extends('customer.layouts.customer')

@section('title', '🌸 Cửa Hàng Hoa Tươi')
@section('page-id', 'dashboard')

@section('content')
<div class="main-wrap">

    @auth
    <div class="welcome-card">
        <div class="welcome-left">
            <div class="welcome-avatar">🌸</div>
            <div class="welcome-info">
                <h2>Xin chào, {{ $user->khachHang ? $user->khachHang->ten_khach_hang : $user->ten_dang_nhap }}!</h2>
                <p>Chào mừng bạn quay lại cửa hàng hoa tươi của chúng tôi 💐</p>
                @if($user->khachHang)
                <p style="margin-top:4px; font-size:.78rem;">
                    📞 {{ $user->khachHang->so_dien_thoai }} &nbsp;|&nbsp;
                    ✉️ {{ $user->khachHang->email }}
                </p>
                @endif
            </div>
        </div>
        @if($user->khachHang)
        <div class="welcome-stats">
            <div class="stat-item">
                <div class="stat-val" style="font-size:1rem;">📍</div>
                <div class="stat-label">{{ $user->khachHang->dia_chi ? Str::limit($user->khachHang->dia_chi, 28) : 'Chưa có địa chỉ' }}</div>
            </div>
        </div>
        @endif
    </div>
    @else
    <div class="guest-banner">
        <div class="guest-banner-left">
            <div class="welcome-avatar">🌸</div>
            <div>
                <h2>Chào mừng đến Hoa Tươi Shop!</h2>
                <p>Đăng nhập để đặt hoa và theo dõi đơn hàng của bạn 💐</p>
            </div>
        </div>
        <div class="guest-banner-actions">
            <a href="{{ route('login') }}" class="btn-primary">Đăng nhập</a>
            <a href="{{ route('register') }}" class="btn-outline">Đăng ký ngay</a>
        </div>
    </div>
    @endauth

    <div class="section-head">
        <h2 class="section-title">Sản Phẩm</h2>
        <span class="result-count" id="resultCount">Đang tải...</span>
    </div>

    <div id="productGrid">
        @for($i = 0; $i < 8; $i++)
        <div class="product-card pulse" style="height:300px;"></div>
        @endfor
        <div id="emptyState">
            <div class="empty-icon">🔍</div>
            <p>Không tìm thấy sản phẩm phù hợp.</p>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    const categoryLabels = {
        'HOA_TUOI':'Hoa Tươi','HOA_GIA':'Hoa Giả','SAN_PHAM_PREMIUM':'Premium',
        'CHAU_HOA_GIA':'Chậu Giả','CHAU_HOA_TUOI':'Chậu Tươi','CAY_CANH':'Cây Cảnh',
        'HOA_SAP':'Hoa Sáp','HOA_GIAY_NHUN':'Hoa Giấy Nhún','TERRARIUM':'Terrarium',
        'PHU_KIEN':'Phụ Kiện','QUA_TANG':'Quà Tặng',
    };
    const catEmoji = {
        'HOA_TUOI':'🌹','HOA_GIA':'💐','SAN_PHAM_PREMIUM':'👑','CHAU_HOA_GIA':'🪴',
        'CHAU_HOA_TUOI':'🌷','CAY_CANH':'🌿','HOA_SAP':'🕯️','HOA_GIAY_NHUN':'🎀',
        'TERRARIUM':'🔮','PHU_KIEN':'🎁','QUA_TANG':'🎀',
    };

    const allProducts = @json($sanPhams ?? []);

    const urlParams  = new URLSearchParams(window.location.search);
    let activeCat    = urlParams.get('cat') || '';
    let searchVal    = urlParams.get('q')   || '';

    if (searchVal) document.getElementById('searchInput').value = searchVal;

    if (activeCat) {
        document.querySelectorAll('#catNav .cat-btn').forEach(b => b.classList.remove('active'));
        const match = document.querySelector(`#catNav .cat-btn[data-cat="${activeCat}"]`);
        if (match) match.classList.add('active');
    } else {
        const first = document.querySelector('#catNav .cat-btn[data-cat=""]');
        if (first) first.classList.add('active');
    }

    function filterCat(cat, _btnEl) {
        activeCat = cat;
        renderProducts();
    }

    function setSearchVal(val) {
        searchVal = val;
        renderProducts();
    }

    function renderProducts() {
        const grid    = document.getElementById('productGrid');
        const empty   = document.getElementById('emptyState');
        const counter = document.getElementById('resultCount');

        const filtered = allProducts.filter(p => {
            const matchCat  = activeCat ? p.loai_san_pham === activeCat : true;
            const matchText = searchVal
                ? p.ten_san_pham.toLowerCase().includes(searchVal.toLowerCase())
                : true;
            return matchCat && matchText;
        });

        Array.from(grid.children).forEach(c => { if (c.id !== 'emptyState') c.remove(); });
        counter.textContent = `${filtered.length} sản phẩm`;

        if (filtered.length === 0) { empty.style.display = 'block'; return; }
        empty.style.display = 'none';

        filtered.forEach(p => {
            const isOnSale   = p.trang_thai === 'DANG_BAN';
            const loGiaAll   = (p.chi_tiet_nhaps || []).filter(l => l.so_luong_con_lai > 0);
            const stock      = loGiaAll.reduce((sum, l) => sum + (l.so_luong_con_lai || 0), 0);
            const stockClass = stock === 0 ? 'out' : stock <= 5 ? 'low' : '';
            const stockText  = stock === 0 ? 'Hết hàng' : `Còn ${stock} sp`;
            const btnDisabled = !isOnSale || stock === 0;
            const detailUrl   = `{{ url('customer/san-pham') }}/` + p.ma_san_pham;

            const loGia = loGiaAll;
            let priceHtml = '';
            if (loGia.length > 0) {
                const giaMin = Math.min(...loGia.map(l => parseFloat(l.gia_ban)));
                const giaMax = Math.max(...loGia.map(l => parseFloat(l.gia_ban)));
                const fmt = v => new Intl.NumberFormat('vi-VN').format(v) + '₫';
                priceHtml = `<div class="card-price">${fmt(giaMin)}${giaMax > giaMin ? ' – ' + fmt(giaMax) : ''}</div>`;
            }

            const card = document.createElement('div');
            card.className = 'product-card';
            card.innerHTML = `
                <div class="card-img-wrap">
                    ${p.hinh_anh
                        ? `<img src="/${p.hinh_anh}" alt="${p.ten_san_pham}"
                               onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                           <div class="card-img-placeholder" style="display:none;">${catEmoji[p.loai_san_pham]||'🌸'}</div>`
                        : `<div class="card-img-placeholder">${catEmoji[p.loai_san_pham]||'🌸'}</div>`}
                    <span class="badge-status ${isOnSale ? 'on-sale' : 'sold-out'}">
                        ${isOnSale ? 'Đang bán' : 'Ngừng bán'}
                    </span>
                    <span class="badge-type">${categoryLabels[p.loai_san_pham]||p.loai_san_pham}</span>
                </div>
                <div class="card-body">
                    <div class="card-name">${p.ten_san_pham}</div>
                    ${priceHtml}
                    ${p.mo_ta ? `<div class="card-desc">${p.mo_ta}</div>` : ''}
                    <div class="card-footer">
                        <span class="card-stock ${stockClass}">${stockText}</span>
                        <button class="add-btn"
                            ${btnDisabled ? 'disabled' : `onclick="navigateTo('${detailUrl}')"`}>
                            ${stock === 0 ? 'Hết hàng' : '🛒 Đặt hoa'}
                        </button>
                    </div>
                </div>`;
            grid.insertBefore(card, empty);
        });
    }

    renderProducts();
</script>
@endsection