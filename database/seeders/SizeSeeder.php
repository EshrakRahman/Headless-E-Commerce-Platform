<?php

namespace Database\Seeders;

use App\Models\Size;
use Illuminate\Database\Seeder;

class SizeSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

        foreach ($sizes as $name) {
            Size::firstOrCreate(['name' => $name]);
        }

        $this->command->info('Created '.count($sizes).' sizes.');
    }
}
