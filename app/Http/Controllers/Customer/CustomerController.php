<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\SanPham;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = auth()->check() ? Auth::user()->load('khachHang') : null;
        $sanPhams = SanPham::where('trang_thai', 'DANG_BAN')
            ->with(['chiTietNhaps' => function ($q) {
                $q->where('so_luong_con_lai', '>', 0)
                  ->whereHas('phieuNhap', fn($q2) => $q2->where('trang_thai', 'CONFIRMED'))
                  ->orderBy('gia_ban', 'asc');
            }])
            ->orderBy('ma_san_pham', 'desc')
            ->get();

        return view('customer.Dashboard', compact('user', 'sanPhams'));
    }
}