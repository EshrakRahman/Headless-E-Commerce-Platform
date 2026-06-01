<?php

use App\Filament\Pages\Reports;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('redirects guests to login page when accessing reports', function () {
    $this->get('/admin/reports')
        ->assertRedirect('/admin/login');
});

it('allows authenticated users to access reports page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/reports')
        ->assertSuccessful();
});

it('loads sales report preview with no errors', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create seed data
    Order::factory()->count(3)->create();

    Livewire::test(Reports::class)
        ->set('data.report_type', 'sales')
        ->assertSet('data.report_type', 'sales')
        ->assertHasNoErrors();
});

it('loads inventory report preview with no errors', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Product::factory()->count(3)->create();

    Livewire::test(Reports::class)
        ->set('data.report_type', 'inventory')
        ->assertSet('data.report_type', 'inventory')
        ->assertHasNoErrors();
});

it('loads promotions report preview with no errors', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Discount::create([
        'name' => 'Promo 1',
        'type' => 'percentage',
        'value' => 10,
        'is_active' => true,
    ]);
    Discount::create([
        'name' => 'Promo 2',
        'type' => 'fixed',
        'value' => 5.00,
        'is_active' => true,
    ]);

    Livewire::test(Reports::class)
        ->set('data.report_type', 'promotions')
        ->assertSet('data.report_type', 'promotions')
        ->assertHasNoErrors();
});

it('loads printable reports successfully when authenticated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('admin.reports.print', [
        'report_type' => 'sales',
        'start_date' => now()->startOfMonth()->format('Y-m-d'),
        'end_date' => now()->endOfMonth()->format('Y-m-d'),
    ]));

    $response->assertSuccessful()
        ->assertViewIs('reports.print-report')
        ->assertSee('Sales Report');
});

it('redirects guest trying to access print route', function () {
    $this->get(route('admin.reports.print', [
        'report_type' => 'sales',
    ]))->assertRedirect('/admin/login');
});

it('allows exporting report data as CSV download', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Seed data
    Order::factory()->create(['total' => 150.00]);

    Livewire::test(Reports::class)
        ->set('data.report_type', 'sales')
        ->call('exportCsv')
        ->assertFileDownloaded();
});
