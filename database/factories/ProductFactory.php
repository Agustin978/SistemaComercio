<?php

namespace Database\Factories;

use App\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Nunca setea stock_on_hand ni uuid: el stock entra por InventoryService y el uuid lo genera el modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = rtrim(fake()->unique()->sentence(3), '.');

        return [
            'category_id' => null,
            'sku' => strtoupper(fake()->unique()->bothify('SKU-????-####')),
            'slug' => Str::slug($name),
            'name' => $name,
            'description' => fake()->optional()->sentence(),
            'price' => fake()->numberBetween(100, 90000).'.'.fake()->numberBetween(0, 99),
            'cost' => fake()->numberBetween(50, 40000).'.00',
            'tax_rate' => '21.00',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
