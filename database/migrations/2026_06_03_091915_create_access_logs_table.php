<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('access_logs');

        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('vai_tro')->nullable();
            $table->string('ip');
            $table->string('url');
            $table->string('method');
            $table->json('required_roles')->nullable();
            $table->timestamp('attempted_at');
            $table->boolean('was_blocked')->default(false);

            // === NÂNG CẤP ===
            $table->string('threat_level')->default('low');   // low | medium | high
            $table->string('blocked_reason')->nullable();      // lý do bị chặn
            $table->text('user_agent')->nullable();            // trình duyệt / thiết bị
            $table->text('request_payload')->nullable();       // body request (phát hiện SQLi/XSS)
            $table->string('attack_type')->nullable();         // BAC | SQLI | XSS | BRUTE_FORCE

            $table->timestamps();

            // Index để query nhanh
            $table->index('threat_level');
            $table->index('was_blocked');
            $table->index('attempted_at');
            $table->index('user_id');
            $table->index('attack_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};