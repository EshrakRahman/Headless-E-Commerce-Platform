<?php

namespace App\Filament\Pages;

use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class Reports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected string $view = 'filament.pages.reports';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'report_type' => 'sales',
            'start_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                Grid::make(3)
                    ->schema([
                        Select::make('report_type')
                            ->label('Report Type')
                            ->options([
                                'sales' => 'Sales Report',
                                'inventory' => 'Inventory Report',
                                'promotions' => 'Promotions Report',
                            ])
                            ->required()
                            ->live(),

                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->visible(fn ($get) => $get('report_type') !== 'inventory')
                            ->required(fn ($get) => $get('report_type') !== 'inventory')
                            ->live(),

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->visible(fn ($get) => $get('report_type') !== 'inventory')
                            ->required(fn ($get) => $get('report_type') !== 'inventory')
                            ->live(),
                    ]),
            ])
            ->statePath('data');
    }

    public function getSalesData(): Collection
    {
        $query = Order::query()->with('user');

        if (! empty($this->data['start_date'])) {
            $query->whereDate('created_at', '>=', $this->data['start_date']);
        }

        if (! empty($this->data['end_date'])) {
            $query->whereDate('created_at', '<=', $this->data['end_date']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getInventoryData(): Collection
    {
        return Product::query()->with(['category', 'sizes'])->orderBy('name', 'asc')->get();
    }

    public function getPromotionsData(): Collection
    {
        $query = Discount::query()->withCount('products');

        if (! empty($this->data['start_date']) && ! empty($this->data['end_date'])) {
            $query->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhereDate('starts_at', '<=', $this->data['end_date']);
            })->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', $this->data['start_date']);
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function exportCsv()
    {
        $reportType = $this->data['report_type'] ?? 'sales';
        $filename = "report-{$reportType}-".now()->format('YmdHis').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($reportType) {
            $file = fopen('php://output', 'w');

            if ($reportType === 'sales') {
                fputcsv($file, ['Order Number', 'Date', 'Customer Name', 'Subtotal ($)', 'Discount ($)', 'Shipping ($)', 'Total ($)', 'Status', 'Payment Status']);
                foreach ($this->getSalesData() as $order) {
                    fputcsv($file, [
                        $order->order_number,
                        $order->created_at->format('Y-m-d H:i:s'),
                        $order->user?->name ?? 'Guest',
                        number_format($order->subtotal, 2),
                        number_format($order->discount, 2),
                        number_format($order->shipping_cost, 2),
                        number_format($order->total, 2),
                        $order->status->value ?? $order->status,
                        $order->payment_status->value ?? $order->payment_status,
                    ]);
                }
            } elseif ($reportType === 'inventory') {
                fputcsv($file, ['Product Name', 'Category', 'SKU/Slug', 'Price ($)', 'Compare Price ($)', 'Stock', 'Inventory Value ($)', 'Featured']);
                foreach ($this->getInventoryData() as $product) {
                    $stock = $product->sizes()->exists()
                        ? $product->sizes()->sum('product_size.stock')
                        : ($product->quantity ?? 0);
                    fputcsv($file, [
                        $product->name,
                        $product->category?->name ?? 'Uncategorized',
                        $product->slug,
                        number_format($product->price, 2),
                        $product->compare_price ? number_format($product->compare_price, 2) : 'N/A',
                        $stock,
                        number_format($product->price * $stock, 2),
                        $product->is_featured ? 'Yes' : 'No',
                    ]);
                }
            } elseif ($reportType === 'promotions') {
                fputcsv($file, ['Promo Name', 'Type', 'Value', 'Status', 'Products Count', 'Starts At', 'Ends At']);
                foreach ($this->getPromotionsData() as $discount) {
                    fputcsv($file, [
                        $discount->name,
                        $discount->type->value ?? $discount->type,
                        $discount->value,
                        $discount->is_active ? 'Active' : 'Inactive',
                        $discount->products_count ?? $discount->products()->count(),
                        $discount->starts_at ? $discount->starts_at->format('Y-m-d H:i:s') : 'Open',
                        $discount->ends_at ? $discount->ends_at->format('Y-m-d H:i:s') : 'Open',
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
