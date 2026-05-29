<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            ['name' => 'Nike', 'description' => 'Athletic shoes and apparel'],
            ['name' => 'Adidas', 'description' => 'Sportswear and lifestyle products'],
            ['name' => 'Apple', 'description' => 'Smartphones, computers, and consumer electronics'],
            ['name' => 'Sony', 'description' => 'Audio, video, gaming, and information technology products'],
            ['name' => 'Levi\'s', 'description' => 'Classic denim apparel and lifestyle brand'],
            ['name' => 'Zara', 'description' => 'Fast fashion clothing and accessories'],
            ['name' => 'Samsung', 'description' => 'Electronics, home appliances, and smart devices'],
            ['name' => 'Dell', 'description' => 'Personal computers, laptops, and technology solutions'],
        ];

        foreach ($brands as $data) {
            Brand::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Created '.count($brands).' brands.');
    }
}
