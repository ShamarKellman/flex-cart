<?php

declare(strict_types=1);

namespace ShamarKellman\FlexCart\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ShamarKellman\FlexCart\Models\Coupon;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->lexify('COUPON-??????')),
            'type' => $this->faker->randomElement([
                Coupon::TYPE_FIXED_AMOUNT,
                Coupon::TYPE_PERCENTAGE,
                Coupon::TYPE_SHIPPING,
            ]),
            'value' => $this->faker->numberBetween(5, 50) * 100,
            'description' => $this->faker->sentence,
            'minimum_amount' => $this->faker->optional(0.3)->numberBetween(10, 100) * 100,
            'usage_limit' => $this->faker->optional(0.5)->numberBetween(1, 100),
            'used_count' => 0,
            'is_active' => true,
            'is_stackable' => $this->faker->boolean(20),
            'starts_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 month', 'now'),
            'expires_at' => $this->faker->optional(0.7)->dateTimeBetween('now', '+6 months'),
            'applies_to' => $this->faker->optional(0.2)->randomElements([1, 2, 3, 4, 5], $this->faker->numberBetween(1, 3)),
            'excludes' => $this->faker->optional(0.1)->randomElements([6, 7, 8, 9, 10], $this->faker->numberBetween(1, 2)),
            'metadata' => $this->faker->optional(0.4)->randomElements([
                'category' => $this->faker->word,
                'campaign' => $this->faker->company,
                'source' => $this->faker->randomElement(['email', 'social', 'website']),
            ], $this->faker->numberBetween(1, 3)),
        ];
    }

    public function fixedAmount(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Coupon::TYPE_FIXED_AMOUNT,
            'value' => $amount * 100,
        ]);
    }

    public function percentage(int $percentage): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Coupon::TYPE_PERCENTAGE,
            'value' => $percentage,
        ]);
    }

    public function shipping(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Coupon::TYPE_SHIPPING,
            'value' => $amount * 100,
        ]);
    }

    public function withMinimumAmount(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'minimum_amount' => $amount * 100,
        ]);
    }

    public function withUsageLimit(int $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => $limit,
        ]);
    }

    public function stackable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_stackable' => true,
        ]);
    }

    public function notStackable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_stackable' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function expiresInDays(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays($days),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(1),
        ]);
    }

    public function startsInDays(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->addDays($days),
        ]);
    }

    public function appliesTo(array $productIds): static
    {
        return $this->state(fn (array $attributes) => [
            'applies_to' => $productIds,
        ]);
    }

    public function excludes(array $productIds): static
    {
        return $this->state(fn (array $attributes) => [
            'excludes' => $productIds,
        ]);
    }

    public function code(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => strtoupper($code),
        ]);
    }
}
