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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10)->unique(); // مثل BTC, ETH, USDT
            $table->string('name'); // نام کامل ارز
            $table->text('description')->nullable(); // توضیحات ارز
            $table->string('image')->nullable(); // مسیر تصویر
            $table->decimal('buy_price', 20, 8)->default(0); // قیمت خرید
            $table->decimal('sell_price', 20, 8)->default(0); // قیمت فروش
            $table->decimal('buy_commission', 5, 2)->default(0); // کارمزد خرید (درصد)
            $table->decimal('sell_commission', 5, 2)->default(0); // کارمزد فروش (درصد)
            $table->decimal('treasury_balance', 20, 8)->default(0); // موجودی خزانه
            $table->boolean('is_active')->default(true); // فعال/غیرفعال
            $table->boolean('is_tradeable')->default(true); // قابل معامله
            $table->integer('decimal_places')->default(8); // تعداد رقم اعشار
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
