<?php

namespace Database\Seeders;

use App\Repositories\ProductJsonRepository;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductInventorySeeder extends Seeder
{
    /**
     * Seed sample rows into storage/app/products.json (JSON array).
     */
    public function run(): void
    {
        /** @var ProductJsonRepository $repo */
        $repo = app(ProductJsonRepository::class);

        $repo->replaceAll([
            [
                'id' => (string) Str::uuid(),
                'product_name' => 'Sample notebook',
                'quantity' => 25,
                'price_per_item' => 4.5,
                'submitted_at' => Carbon::now()->subDays(3)->toIso8601String(),
            ],
            [
                'id' => (string) Str::uuid(),
                'product_name' => 'Demo mug',
                'quantity' => 12,
                'price_per_item' => 8.0,
                'submitted_at' => Carbon::now()->subDay()->toIso8601String(),
            ],
            [
                'id' => (string) Str::uuid(),
                'product_name' => 'Starter pen pack',
                'quantity' => 100,
                'price_per_item' => 0.35,
                'submitted_at' => Carbon::now()->subHours(5)->toIso8601String(),
            ],
        ]);
    }
}
