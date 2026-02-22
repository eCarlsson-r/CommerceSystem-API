<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Banner;
use App\Models\Media;
use Faker\Factory as Faker;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $banners = [
            [
                'title' => 'Welcome to Zard Store',
                'description' => 'Discover amazing products at great prices',
                'link_url' => '/shop',
                'order_priority' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'New Arrivals',
                'description' => 'Check out our latest collection',
                'link_url' => '/shop?category=new',
                'order_priority' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Special Discount',
                'description' => 'Up to 50% off on selected items',
                'link_url' => '/shop?discount=true',
                'order_priority' => 3,
                'is_active' => true,
            ],
        ];

        // Add more dummy banners
        for ($i = 0; $i < 5; $i++) {
            $banners[] = [
                'title' => $faker->sentence(3),
                'description' => $faker->sentence(6),
                'link_url' => $faker->url(),
                'order_priority' => $i + 4,
                'is_active' => $faker->boolean(80), // 80% chance active
            ];
        }

        foreach ($banners as $bData) {
            $banner = Banner::updateOrCreate(
                ['title' => $bData['title']],
                $bData
            );
        }
    }
}
