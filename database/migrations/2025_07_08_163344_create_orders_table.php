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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // شماره سفارش
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['buy', 'sell', 'exchange']); // نوع سفارش
            $table->foreignId('from_currency_id')->constrained('currencies'); // ارز مبدا
            $table->foreignId('to_currency_id')->constrained('currencies'); // ارز مقصد
            $table->decimal('from_amount', 20, 8); // مقدار ارز مبدا
            $table->decimal('to_amount', 20, 8); // مقدار ارز مقصد
            $table->decimal('exchange_rate', 20, 8); // نرخ تبدیل
            $table->decimal('commission_rate', 5, 2); // نرخ کارمزد
            $table->decimal('commission_amount', 20, 8); // مقدار کارمزد
            $table->decimal('final_amount', 20, 8); // مقدار نهایی
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled', 'failed'])->default('pending');
            $table->string('discount_code')->nullable(); // کد تخفیف استفاده شده
            $table->decimal('discount_amount', 20, 8)->default(0); // مقدار تخفیف
            $table->json('metadata')->nullable(); // اطلاعات اضافی
            $table->timestamp('processed_at')->nullable();
            $table->text('cancellation_reason')->nullable(); // دلیل لغو
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
