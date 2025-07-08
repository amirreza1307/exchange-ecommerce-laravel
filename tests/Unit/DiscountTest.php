<?php

namespace Tests\Unit;

use App\Models\Discount;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_is_active_scope()
    {
        Discount::factory()->create(['is_active' => true]);
        Discount::factory()->create(['is_active' => false]);

        $this->assertEquals(1, Discount::active()->count());
    }

    public function test_discount_is_not_expired_scope()
    {
        Discount::factory()->create(['expires_at' => Carbon::now()->addDay()]);
        Discount::factory()->create(['expires_at' => Carbon::now()->subDay()]);

        $this->assertEquals(1, Discount::notExpired()->count());
    }

    public function test_discount_available_scope()
    {
        // Active and not expired
        Discount::factory()->create([
            'is_active' => true,
            'expires_at' => Carbon::now()->addDay(),
            'usage_limit' => 10,
            'used_count' => 5
        ]);

        // Inactive
        Discount::factory()->create([
            'is_active' => false,
            'expires_at' => Carbon::now()->addDay(),
            'usage_limit' => 10,
            'used_count' => 5
        ]);

        // Expired
        Discount::factory()->create([
            'is_active' => true,
            'expires_at' => Carbon::now()->subDay(),
            'usage_limit' => 10,
            'used_count' => 5
        ]);

        // Max usage reached
        Discount::factory()->create([
            'is_active' => true,
            'expires_at' => Carbon::now()->addDay(),
            'usage_limit' => 10,
            'used_count' => 10
        ]);

        $this->assertEquals(1, Discount::available()->count());
    }

    public function test_discount_is_expired_method()
    {
        $expiredDiscount = Discount::factory()->create([
            'expires_at' => Carbon::now()->subDay()
        ]);

        $validDiscount = Discount::factory()->create([
            'expires_at' => Carbon::now()->addDay()
        ]);

        $this->assertTrue($expiredDiscount->isExpired());
        $this->assertFalse($validDiscount->isExpired());
    }

    public function test_discount_is_max_usage_reached_method()
    {
        $maxReachedDiscount = Discount::factory()->create([
            'usage_limit' => 10,
            'used_count' => 10
        ]);

        $validDiscount = Discount::factory()->create([
            'usage_limit' => 10,
            'used_count' => 5
        ]);

        $this->assertTrue($maxReachedDiscount->isMaxUsageReached());
        $this->assertFalse($validDiscount->isMaxUsageReached());
    }

    public function test_discount_is_available_method()
    {
        $availableDiscount = Discount::factory()->create([
            'is_active' => true,
            'expires_at' => Carbon::now()->addDay(),
            'usage_limit' => 10,
            'used_count' => 5
        ]);

        $unavailableDiscount = Discount::factory()->create([
            'is_active' => false,
            'expires_at' => Carbon::now()->addDay(),
            'usage_limit' => 10,
            'used_count' => 5
        ]);

        $this->assertTrue($availableDiscount->isAvailable());
        $this->assertFalse($unavailableDiscount->isAvailable());
    }

    public function test_discount_calculate_discount_percentage()
    {
        $percentageDiscount = Discount::factory()->create([
            'type' => 'percentage',
            'value' => 20
        ]);

        $this->assertEquals(2000, $percentageDiscount->calculateDiscount(10000));
        $this->assertEquals(1000, $percentageDiscount->calculateDiscount(5000));
    }

    public function test_discount_calculate_discount_fixed()
    {
        $fixedDiscount = Discount::factory()->create([
            'type' => 'fixed',
            'value' => 5000
        ]);

        $this->assertEquals(5000, $fixedDiscount->calculateDiscount(10000));
        $this->assertEquals(5000, $fixedDiscount->calculateDiscount(20000));
    }

    public function test_discount_calculate_discount_with_min_amount()
    {
        $discount = Discount::factory()->create([
            'type' => 'percentage',
            'value' => 10,
            'min_order_amount' => 5000
        ]);

        // Amount meets minimum
        $this->assertEquals(1000, $discount->calculateDiscount(10000));
        
        // Amount below minimum
        $this->assertEquals(0, $discount->calculateDiscount(3000));
    }

    public function test_discount_increment_usage()
    {
        $discount = Discount::factory()->create([
            'used_count' => 5
        ]);

        $discount->incrementUsage();

        $this->assertEquals(6, $discount->fresh()->used_count);
    }

    public function test_discount_can_be_used_method()
    {
        $validDiscount = Discount::factory()->create([
            'is_active' => true,
            'expires_at' => Carbon::now()->addDay(),
            'usage_limit' => 10,
            'used_count' => 5,
            'min_order_amount' => 1000
        ]);

        $this->assertTrue($validDiscount->canBeUsed(5000));
        $this->assertFalse($validDiscount->canBeUsed(500)); // Below min amount
    }

    public function test_discount_find_by_code()
    {
        $discount = Discount::factory()->create([
            'code' => 'SAVE20',
            'is_active' => true,
            'expires_at' => Carbon::now()->addDay(),
            'usage_limit' => 10,
            'used_count' => 5
        ]);

        $foundDiscount = Discount::findByCode('SAVE20');
        $this->assertNotNull($foundDiscount);
        $this->assertEquals($discount->id, $foundDiscount->id);

        $notFoundDiscount = Discount::findByCode('INVALID');
        $this->assertNull($notFoundDiscount);
    }
}
