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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_currency_id')->constrained('currencies');
            $table->foreignId('to_currency_id')->constrained('currencies');
            $table->decimal('rate', 20, 8); // نرخ تبدیل
            $table->decimal('buy_rate', 20, 8)->nullable(); // نرخ خرید
            $table->decimal('sell_rate', 20, 8)->nullable(); // نرخ فروش
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
            
            $table->unique(['from_currency_id', 'to_currency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
