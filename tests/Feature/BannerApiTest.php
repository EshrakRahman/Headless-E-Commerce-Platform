<?php

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns active hero banners ordered by sort_order', function () {
    Banner::create([
        'title' => 'Third Banner',
        'cta_text' => 'Shop',
        'cta_url' => '/shop',
        'sort_order' => 3,
        'is_active' => true,
    ]);

    Banner::create([
        'title' => 'First Banner',
        'cta_text' => 'Shop',
        'cta_url' => '/shop',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    Banner::create([
        'title' => 'Inactive Banner',
        'cta_text' => 'Shop',
        'cta_url' => '/shop',
        'sort_order' => 2,
        'is_active' => false,
    ]);

    $response = $this->getJson('/api/v1/banners');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', 'First Banner')
        ->assertJsonPath('data.1.title', 'Third Banner');
});

it('filters banners by position', function () {
    Banner::create([
        'title' => 'Hero Banner',
        'cta_text' => 'Shop',
        'cta_url' => '/shop',
        'position' => 'hero',
        'is_active' => true,
    ]);

    Banner::create([
        'title' => 'Sidebar Banner',
        'cta_text' => 'Shop',
        'cta_url' => '/shop',
        'position' => 'sidebar',
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/v1/banners?position=hero');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Hero Banner');
});

it('excludes banners with future starts_at', function () {
    Banner::create([
        'title' => 'Active Banner',
        'cta_text' => 'Shop',
        'cta_url' => '/shop',
        'is_active' => true,
    ]);

    Banner::create([
        'title' => 'Future Banner',
        'cta_text' => 'Shop',
        'cta_url' => '/shop',
        'is_active' => true,
        'starts_at' => now()->addDays(7),
    ]);

    $response = $this->getJson('/api/v1/banners');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Active Banner');
});

it('excludes banners with past ends_at', function () {
    Banner::create([
        'title' => 'Active Banner',
        'cta_text' => 'Shop',
        'cta_url' => '/shop',
        'is_active' => true,
    ]);

    Banner::create([
        'title' => 'Expired Banner',
        'cta_text' => 'Shop',
        'cta_url' => '/shop',
        'is_active' => true,
        'ends_at' => now()->subDays(7),
    ]);

    $response = $this->getJson('/api/v1/banners');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Active Banner');
});

it('returns empty data when no active banners exist', function () {
    $response = $this->getJson('/api/v1/banners');

    $response->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

it('formats desktop and mobile image URLs correctly', function () {
    Banner::create([
        'title' => 'External Image Banner',
        'cta_text' => 'Shop',
        'cta_url' => '/shop',
        'desktop_image' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f',
        'mobile_image' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f',
        'is_active' => true,
    ]);

    Banner::create([
        'title' => 'S3 Image Banner',
        'cta_text' => 'Shop',
        'cta_url' => '/shop',
        'desktop_image' => 'banners/desktop.jpg',
        'mobile_image' => 'banners/mobile.jpg',
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/v1/banners');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data');

    // Assert External URLs are returned as-is
    $external = collect($response->json('data'))->firstWhere('title', 'External Image Banner');
    expect($external['desktop_image'])->toBe('https://images.unsplash.com/photo-1515886657613-9f3515b0c78f');
    expect($external['mobile_image'])->toBe('https://images.unsplash.com/photo-1515886657613-9f3515b0c78f');

    // Assert S3 paths are prefixed with S3 storage URL
    $s3Banner = collect($response->json('data'))->firstWhere('title', 'S3 Image Banner');
    expect($s3Banner['desktop_image'])->toContain('banners/desktop.jpg');
    expect($s3Banner['mobile_image'])->toContain('banners/mobile.jpg');
});
