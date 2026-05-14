<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Schemas\Components;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('index')
                    ->label('No')
                    ->rowIndex(),

                ImageColumn::make('image')
                    ->label('Photo')
                    ->square()
                    ->disk('s3')
                    ->size(50)
                    ->defaultImageUrl(fn () => 'https://placehold.co/50x50?text='.urlencode('No Image')),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->slug)
                    ->wrap(),

                TextColumn::make('category.name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('price')
                    ->money()
                    ->sortable()
                    ->description(fn ($record) => $record->compare_price && $record->compare_price > $record->price
                        ? 'Was '.number_format($record->compare_price, 2)
                        : null
                    )
                    ->color(fn ($record) => $record->compare_price && $record->compare_price > $record->price
                        ? Color::Red
                        : null
                    ),

                TextColumn::make('stock_status')
                    ->label('Stock')
                    ->getStateUsing(function ($record) {
                        if ($record->sizes()->exists()) {
                            $total = $record->sizes()->sum('product_size.stock');

                            return $total;
                        }

                        return $record->quantity;
                    })
                    ->formatStateUsing(fn ($state): string => match (true) {
                        $state > 10 => 'In Stock ('.$state.')',
                        $state > 0 && $state <= 10 => 'Low ('.$state.')',
                        default => 'Out of Stock',
                    })
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state > 10 => 'success',
                        $state > 0 && $state <= 10 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query->orderBy('quantity', $direction);
                    }),

                TextColumn::make('sizes_count')
                    ->label('Sizes')
                    ->counts('sizes')
                    ->badge()
                    ->color(fn ($state): string => $state > 0 ? 'info' : 'gray')
                    ->toggleable(),

                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->date('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->date('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_featured')
                    ->label('Featured'),

                SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'low' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! $data['value']) {
                            return;
                        }

                        match ($data['value']) {
                            'in_stock' => $query->where(function (Builder $q) {
                                $q->whereDoesntHave('sizes')
                                    ->where('quantity', '>', 10);

                                $q->orWhere(function (Builder $q) {
                                    $q->whereHas('productSizes', function (Builder $q) {
                                        $q->select(DB::raw('product_id'))
                                            ->groupBy('product_id')
                                            ->havingRaw('SUM(stock) > 10');
                                    });
                                });
                            }),

                            'low' => $query->where(function (Builder $q) {
                                $q->whereDoesntHave('sizes')
                                    ->where('quantity', '>', 0)
                                    ->where('quantity', '<=', 10);

                                $q->orWhere(function (Builder $q) {
                                    $q->whereHas('productSizes', function (Builder $q) {
                                        $q->select(DB::raw('product_id'))
                                            ->groupBy('product_id')
                                            ->havingRaw('SUM(stock) > 0 AND SUM(stock) <= 10');
                                    });
                                });
                            }),

                            'out_of_stock' => $query->where(function (Builder $q) {
                                $q->whereDoesntHave('sizes')
                                    ->where(function (Builder $q) {
                                        $q->where('quantity', '<=', 0)
                                            ->orWhereNull('quantity');
                                    });

                                $q->orWhere(function (Builder $q) {
                                    $q->whereHas('sizes')
                                        ->whereDoesntHave('sizes', function (Builder $q) {
                                            $q->where('stock', '>', 0);
                                        });
                                });
                            }),
                        };
                    }),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
