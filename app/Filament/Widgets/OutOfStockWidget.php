<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Inventory\InventoryResource;
use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OutOfStockWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->whereNull('deleted_at')
                    ->where(function ($query) {
                        $query->where(function ($q) {
                            $q->where(function ($q) {
                                $q->where('quantity', '<=', 0)
                                    ->orWhereNull('quantity');
                            })->whereDoesntHave('sizes');
                        })->orWhere(function ($q) {
                            $q->whereDoesntHave('sizes', function ($sq) {
                                $sq->where('stock', '>', 0);
                            })->whereHas('sizes');
                        });
                    })
                    ->orderBy('created_at', 'desc')
            )
            ->heading('Out of Stock Products')
            ->description('Products with zero or no stock')
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
                    ->color('danger'),
            ])
            ->paginated(false);
    }
}
