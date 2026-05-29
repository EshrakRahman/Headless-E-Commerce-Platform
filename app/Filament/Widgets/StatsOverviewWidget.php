<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Discount;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $revenue = Order::where('payment_status', PaymentStatus::Paid)->sum('total');
        $ordersCount = Order::count();
        $avgOrderValue = Order::where('payment_status', PaymentStatus::Paid)->avg('total') ?? 0;
        $activeDiscounts = Discount::active()->count();

        return [
            Stat::make('Total Revenue', '$'.number_format($revenue, 2))
                ->description('Total earnings from paid orders')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Total Orders', number_format($ordersCount))
                ->description('Total orders placed')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info'),
            Stat::make('Average Order Value', '$'.number_format($avgOrderValue, 2))
                ->description('Average amount spent per paid order')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning'),
            Stat::make('Active Promotions', number_format($activeDiscounts))
                ->description('Currently active discount campaigns')
                ->descriptionIcon('heroicon-m-gift')
                ->color('success'),
        ];
    }
}
