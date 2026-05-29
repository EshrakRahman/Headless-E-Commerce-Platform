<?php

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('seeds exactly 100 products equally distributed across 5 categories', function () {
    // Run the seeders
    $this->seed();

    // Assert total products count is exactly 100
    expect(Product::count())->toBe(100);

    // Assert categories count is exactly 5
    expect(Category::count())->toBe(5);

    // Assert that each category has exactly 20 products
    $categories = Category::all();
    foreach ($categories as $category) {
        expect($category->products()->count())->toBe(20);
    }
});

it('seeds exactly 3 banners', function () {
    $this->seed();

    // Assert banners count is exactly 3
    expect(Banner::count())->toBe(3);
});

it('is idempotent on multiple runs', function () {
    $this->seed();
    $this->seed();

    expect(Product::count())->toBe(100);
    expect(Banner::count())->toBe(3);
});

it('does not overwrite admin modified products during reseeding', function () {
    $this->seed();

    // Find a seeded product and modify it
    $product = Product::first();
    $originalName = $product->name;

    // Simulate admin modifications
    $product->created_at = now()->subMinutes(5);
    $product->price = 999.99;
    $product->description = 'Admin modified description';
    $product->save(); // This updates updated_at, making it != created_at

    // Re-run the seeders
    $this->seed();

    // Refresh model and assert that admin changes were preserved
    $product->refresh();
    expect((float) $product->price)->toBe(999.99);
    expect($product->description)->toBe('Admin modified description');
    expect($product->name)->toBe($originalName);
});

it('does not overwrite admin modified banners during reseeding', function () {
    $this->seed();

    // Find a seeded banner and modify it
    $banner = Banner::first();
    $originalTitle = $banner->title;

    // Simulate admin modifications
    $banner->created_at = now()->subMinutes(5);
    $banner->subtitle = 'Admin modified subtitle';
    $banner->save(); // Updates updated_at, making it != created_at

    // Re-run the seeders
    $this->seed();

    // Refresh and assert changes were preserved
    $banner->refresh();
    expect($banner->subtitle)->toBe('Admin modified subtitle');
    expect($banner->title)->toBe($originalTitle);
});
