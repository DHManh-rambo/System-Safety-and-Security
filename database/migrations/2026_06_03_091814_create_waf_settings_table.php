<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waf_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();            // cột key
            $table->boolean('value')->default(false);   // cột value
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waf_settings');
    }
};