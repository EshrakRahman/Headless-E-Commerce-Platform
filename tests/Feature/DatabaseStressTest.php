<?php

use App\Enums\DiscountType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\getJson;

uses(LazilyRefreshDatabase::class);

it('correctly calculates sale price with enum types', function () {
    $category = Category::factory()->create();

    $product1 = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 100.00,
    ]);

    $product2 = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 100.00,
    ]);

    // Percentage discount
    $percentageDiscount = Discount::create([
        'name' => '30% Off',
        'type' => DiscountType::Percentage,
        'value' => 30.00,
        'is_active' => true,
    ]);

    // Fixed discount
    $fixedDiscount = Discount::create([
        'name' => '$15 Off',
        'type' => DiscountType::Fixed,
        'value' => 15.00,
        'is_active' => true,
    ]);

    $percentageDiscount->products()->attach($product1->id);
    $fixedDiscount->products()->attach($product2->id);

    // Refresh models to load relations/accessors correctly
    $product1->load('discounts');
    $product2->load('discounts');

    // Asserts:
    // Product 1: 100 * (1 - 0.3) = 70.00
    expect((float) $product1->sale_price)->toEqual(70.00);

    // Product 2: 100 - 15 = 85.00
    expect((float) $product2->sale_price)->toEqual(85.00);
});

it('prevents N+1 queries when fetching products', function () {
    $category = Category::factory()->create();

    // Create 5 products
    Product::factory()->count(5)->create([
        'category_id' => $category->id,
    ]);

    DB::enableQueryLog();

    $response = getJson('/api/v1/products');

    $response->assertSuccessful();

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Verify pagination is active: response should have pagination structure
    $response->assertJsonStructure([
        'data',
        'links',
        'meta',
    ]);

    // The query count should be small and constant (typically 5 queries)
    expect(count($queries))->toBeLessThan(10);
});

it('can filter products by brand', function () {
    $category = Category::factory()->create();
    $brand1 = Brand::create(['name' => 'Nike', 'slug' => 'nike']);
    $brand2 = Brand::create(['name' => 'Adidas', 'slug' => 'adidas']);

    Product::factory()->create([
        'category_id' => $category->id,
        'brand_id' => $brand1->id,
        'name' => 'Nike Shoes',
    ]);

    Product::factory()->create([
        'category_id' => $category->id,
        'brand_id' => $brand2->id,
        'name' => 'Adidas Shoes',
    ]);

    $response = getJson('/api/v1/products?brand=nike');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Nike Shoes');
});
