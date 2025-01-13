<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Produk>
 */
class ProdukFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_produk' => fake()->unique()->word(),
            'harga_beli' => fake()->numberBetween(1000, 100000),
            'harga_jual' => fake()->numberBetween(1000, 100000),
            'stok' => fake()->numberBetween(0, 100),
            'stok_minimal' => fake()->numberBetween(0, 100),
            'tanggal_kadaluarsa' => fake()->optional()->date(),
            'keterangan' => fake()->optional()->sentence(),
        ];
    }
}
