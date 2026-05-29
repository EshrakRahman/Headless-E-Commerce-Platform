<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'title' => 'Summer Collection 2026',
                'subtitle' => 'Up to 40% off on summer essentials',
                'cta_text' => 'Shop Now',
                'cta_url' => '/new-arrivals',
                'desktop_image' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=1600&auto=format&fit=crop&q=80',
                'mobile_image' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=800&auto=format&fit=crop&q=80',
                'bg_color' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'text_color' => 'light',
                'sort_order' => 1,
            ],
            [
                'title' => 'New Season Styles',
                'subtitle' => 'Fresh looks for every occasion',
                'cta_text' => 'Explore',
                'cta_url' => '/collections/new',
                'desktop_image' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1600&auto=format&fit=crop&q=80',
                'mobile_image' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800&auto=format&fit=crop&q=80',
                'bg_color' => '#F2F0F1',
                'text_color' => 'dark',
                'sort_order' => 2,
            ],
            [
                'title' => 'Limited Time Deals',
                'subtitle' => 'Up to 60% off selected items — hurry, ends soon',
                'cta_text' => 'View Deals',
                'cta_url' => '/sale',
                'desktop_image' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1600&auto=format&fit=crop&q=80',
                'mobile_image' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=800&auto=format&fit=crop&q=80',
                'bg_color' => 'linear-gradient(135deg, #f093fb 0%, #fa709a 100%)',
                'text_color' => 'light',
                'sort_order' => 3,
            ],
        ];

        foreach ($banners as $banner) {
            $existing = Banner::where('title', $banner['title'])->first();
            if ($existing && $existing->updated_at != $existing->created_at) {
                continue;
            }

            Banner::updateOrCreate(
                ['title' => $banner['title']],
                $banner
            );
        }

        $this->command->info('Created '.count($banners).' banners.');
    }
}
