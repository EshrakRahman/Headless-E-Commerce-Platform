<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Inventory\InventoryResource;
use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->whereNull('deleted_at')
                    ->where(function ($query) {
                        $query->where(function ($q) {
                            $q->where('quantity', '>', 0)
                                ->where('quantity', '<=', 10)
                                ->whereDoesntHave('sizes');
                        })->orWhereHas('sizes', function ($q) {
                            $q->selectRaw('SUM(product_size.stock) as total_stock')
                                ->havingRaw('COALESCE(SUM(product_size.stock), 0) > 0')
                                ->havingRaw('COALESCE(SUM(product_size.stock), 0) <= 10');
                        });
                    })
                    ->orderBy('created_at', 'desc')
            )
            ->heading('Low Stock Products')
            ->description('Products with stock levels between 1 and 10')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->url(fn ($record) => InventoryResource::getUrl('view', ['record' => $record])),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge(),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->getStateUsing(function ($record) {
                        if ($record->sizes()->exists()) {
                            return $record->sizes()->sum('product_size.stock');
                        }

                        return $record->quantity ?? 0;
                    })
                    ->badge()
                    ->color('warning'),
            ])
            ->paginated(false);
    }
}
