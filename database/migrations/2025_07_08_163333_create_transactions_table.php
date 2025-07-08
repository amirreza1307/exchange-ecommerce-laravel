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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique(); // شناسه منحصربه‌فرد تراکنش
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('currency_id')->constrained();
            $table->enum('type', ['deposit', 'withdraw', 'buy', 'sell', 'exchange']); // نوع تراکنش
            $table->decimal('amount', 20, 8); // مقدار
            $table->decimal('fee', 20, 8)->default(0); // کارمزد
            $table->decimal('final_amount', 20, 8); // مقدار نهایی بعد از کسر کارمزد
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('reference_id')->nullable(); // شناسه مرجع (برای تراکنش‌های بانکی)
            $table->json('metadata')->nullable(); // اطلاعات اضافی
            $table->text('description')->nullable(); // توضیحات
            $table->timestamp('processed_at')->nullable(); // زمان پردازش
            $table->timestamps();
            
            $table->index(['user_id', 'type']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
