<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bao_cao_hang_hong', function (Blueprint $table) {
            $table->id('ma_bao_cao');

            $table->unsignedBigInteger('ma_san_pham');
            $table->unsignedBigInteger('ma_chi_tiet_nhap')->nullable();
            $table->unsignedBigInteger('ma_nhan_vien');

            $table->integer('so_luong_hong')->unsigned();
            $table->string('ly_do', 255)->nullable();
            $table->text('ghi_chu')->nullable();
            $table->dateTime('thoi_gian_bao_cao')->useCurrent();

            $table->foreign('ma_san_pham')
                  ->references('ma_san_pham')
                  ->on('san_pham');

            $table->foreign('ma_chi_tiet_nhap')
                  ->references('ma_chi_tiet_nhap')
                  ->on('chi_tiet_nhap')
                  ->onDelete('set null');

            $table->foreign('ma_nhan_vien')
                  ->references('ma_nhan_vien')
                  ->on('nhan_vien');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bao_cao_hang_hong');
    }
};