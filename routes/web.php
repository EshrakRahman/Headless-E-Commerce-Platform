<?php

use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

Route::middleware(['web', Authenticate::class])->get('/admin/reports/print', function (Request $request) {
    $reportType = $request->query('report_type', 'sales');
    $startDate = $request->query('start_date');
    $endDate = $request->query('end_date');

    $data = collect();

    if ($reportType === 'sales') {
        $query = Order::query()->with('user');
        if (! empty($startDate)) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if (! empty($endDate)) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        $data = $query->orderBy('created_at', 'desc')->get();
    } elseif ($reportType === 'inventory') {
        $data = Product::query()->with(['category', 'sizes'])->orderBy('name', 'asc')->get();
    } elseif ($reportType === 'promotions') {
        $query = Discount::query()->withCount('products');
        if (! empty($startDate) && ! empty($endDate)) {
            $query->where(function ($q) use ($endDate) {
                $q->whereNull('starts_at')
                    ->orWhereDate('starts_at', '<=', $endDate);
            })->where(function ($q) use ($startDate) {
                $q->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', $startDate);
            });
        }
        $data = $query->orderBy('created_at', 'desc')->get();
    }

    return view('reports.print-report', [
        'reportType' => $reportType,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'data' => $data,
    ]);
})->name('admin.reports.print');
