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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // کد تخفیف
            $table->string('title'); // عنوان تخفیف
            $table->text('description')->nullable(); // توضیحات
            $table->enum('type', ['percentage', 'fixed']); // نوع تخفیف: درصدی یا مقدار ثابت
            $table->decimal('value', 10, 2); // مقدار تخفیف
            $table->decimal('min_order_amount', 20, 8)->nullable(); // حداقل مبلغ سفارش
            $table->decimal('max_discount_amount', 20, 8)->nullable(); // حداکثر مقدار تخفیف
            $table->integer('usage_limit')->nullable(); // محدودیت تعداد استفاده
            $table->integer('used_count')->default(0); // تعداد استفاده شده
            $table->integer('user_usage_limit')->nullable(); // محدودیت استفاده هر کاربر
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable(); // زمان شروع
            $table->timestamp('expires_at')->nullable(); // زمان انقضا
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
