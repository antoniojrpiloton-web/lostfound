<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            [
                'slug' => 'electronics',
                'labels' => ['en' => 'Electronics', 'fil' => 'Elektroniks'],
                'sort_order' => 10,
                'children' => [
                    ['slug' => 'mobile-phones', 'labels' => ['en' => 'Mobile Phones', 'fil' => 'Cellphone'], 'sort_order' => 11],
                    ['slug' => 'cameras-action-cams', 'labels' => ['en' => 'Cameras & Action Cams', 'fil' => 'Kamera'], 'sort_order' => 12],
                    ['slug' => 'audio-wearables', 'labels' => ['en' => 'Earphones & Smartwatches', 'fil' => 'Earphones at Smartwatch'], 'sort_order' => 13],
                    ['slug' => 'laptops-tablets', 'labels' => ['en' => 'Laptops & Tablets', 'fil' => 'Laptop at Tablet'], 'sort_order' => 14],
                ],
            ],
            [
                'slug' => 'wallets-bags',
                'labels' => ['en' => 'Wallets & Bags', 'fil' => 'Pitaka at Bag'],
                'sort_order' => 20,
                'children' => [
                    ['slug' => 'wallets-purses', 'labels' => ['en' => 'Wallets & Purses', 'fil' => 'Pitaka'], 'sort_order' => 21],
                    ['slug' => 'backpacks-bags', 'labels' => ['en' => 'Backpacks & Dry Bags', 'fil' => 'Backpack at Dry Bag'], 'sort_order' => 22],
                    ['slug' => 'luggage', 'labels' => ['en' => 'Luggage & Suitcases', 'fil' => 'Maleta'], 'sort_order' => 23],
                ],
            ],
            [
                'slug' => 'ids-documents',
                'labels' => ['en' => 'IDs & Documents', 'fil' => 'ID at Dokumento'],
                'sort_order' => 30,
                'children' => [
                    ['slug' => 'government-ids', 'labels' => ['en' => 'Government IDs', 'fil' => 'ID ng Gobyerno'], 'sort_order' => 31],
                    ['slug' => 'passports', 'labels' => ['en' => 'Passports & Travel Documents', 'fil' => 'Pasaporte at Travel Document'], 'sort_order' => 32],
                    ['slug' => 'bank-cards', 'labels' => ['en' => 'ATM & Credit Cards', 'fil' => 'ATM at Credit Card'], 'sort_order' => 33],
                ],
            ],
            [
                'slug' => 'personal-accessories',
                'labels' => ['en' => 'Personal Accessories', 'fil' => 'Personal na Gamit'],
                'sort_order' => 40,
                'children' => [
                    ['slug' => 'jewelry-watches', 'labels' => ['en' => 'Jewelry & Watches', 'fil' => 'Alahas at Relo'], 'sort_order' => 41],
                    ['slug' => 'eyewear', 'labels' => ['en' => 'Eyeglasses & Sunglasses', 'fil' => 'Salamin sa Mata'], 'sort_order' => 42],
                    ['slug' => 'keys', 'labels' => ['en' => 'Keys & Keycards', 'fil' => 'Susi at Keycard'], 'sort_order' => 43],
                ],
            ],
            [
                'slug' => 'clothing-beachwear',
                'labels' => ['en' => 'Clothing & Beachwear', 'fil' => 'Damit at Pang-beach'],
                'sort_order' => 50,
                'children' => [],
            ],
            [
                'slug' => 'others',
                'labels' => ['en' => 'Others', 'fil' => 'Iba pa'],
                'sort_order' => 99,
                'children' => [],
            ],
        ];

        foreach ($tree as $item) {
            $parent = Category::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'parent_id' => null,
                    'labels' => $item['labels'],
                    'is_active' => true,
                    'sort_order' => $item['sort_order'],
                ]
            );

            foreach ($item['children'] as $child) {
                Category::query()->updateOrCreate(
                    ['slug' => $child['slug']],
                    [
                        'parent_id' => $parent->id,
                        'labels' => $child['labels'],
                        'is_active' => true,
                        'sort_order' => $child['sort_order'],
                    ]
                );
            }
        }
    }
}
