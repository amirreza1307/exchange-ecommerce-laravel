<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('currency_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 20, 8)->default(0); // موجودی کیف پول
            $table->decimal('frozen_balance', 20, 8)->default(0); // موجودی مسدود شده
            $table->timestamps();
            
            $table->unique(['user_id', 'currency_id']); // هر کاربر برای هر ارز فقط یک کیف پول
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
