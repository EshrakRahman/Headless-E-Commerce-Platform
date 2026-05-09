<?php

namespace App\Filament\Resources\Inventory\Tables;

use App\Models\ProductSize;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class InventoriesTable
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
                    ->size(40)
                    ->defaultImageUrl(fn () => 'https://placehold.co/40x40?text='.urlencode('No Image')),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->slug)
                    ->wrap(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stock_type')
                    ->label('Type')
                    ->getStateUsing(fn ($record) => $record->sizes()->exists() ? 'Sized' : 'Simple')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Sized' ? 'info' : 'gray'),

                TextColumn::make('current_stock')
                    ->label('Stock')
                    ->getStateUsing(function ($record) {
                        if ($record->sizes()->exists()) {
                            return $record->sizes()->sum('product_size.stock');
                        }

                        return $record->quantity ?? 0;
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
                    }),

                TextColumn::make('last_movement')
                    ->label('Last Movement')
                    ->getStateUsing(fn ($record) => StockMovement::where('product_id', $record->id)
                        ->latest()
                        ->value('created_at')
                    )
                    ->date('M j, Y H:i')
                    ->placeholder('Never'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

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
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),

                    Action::make('adjust')
                        ->label('Adjust Stock')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form(function ($record) {
                            $fields = [];

                            if ($record->sizes()->exists()) {
                                $fields[] = Select::make('product_size_id')
                                    ->label('Size')
                                    ->options($record->sizes->pluck('name', 'pivot.id'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) use ($record) {
                                        $size = $record->productSizes()->find($state);
                                        $set('current_stock_display', $size?->stock ?? 0);
                                    });

                                $fields[] = TextInput::make('current_stock_display')
                                    ->label('Current Stock')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(0);
                            } else {
                                $fields[] = TextInput::make('current_stock_display')
                                    ->label('Current Stock')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default($record->quantity ?? 0);
                            }

                            $fields[] = Radio::make('adjustment_type')
                                ->label('Adjustment Type')
                                ->options([
                                    'add' => 'Add Stock',
                                    'remove' => 'Remove Stock',
                                    'set' => 'Set Stock',
                                ])
                                ->default('add')
                                ->required()
                                ->live();

                            $fields[] = TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->required()
                                ->minValue(1);

                            $fields[] = Textarea::make('reason')
                                ->label('Reason')
                                ->required()
                                ->maxLength(500);

                            return $fields;
                        })
                        ->action(function (array $data, $record) {
                            $service = app(InventoryService::class);
                            $user = auth()->user();

                            if ($record->sizes()->exists() && isset($data['product_size_id'])) {
                                $productSize = ProductSize::find($data['product_size_id']);

                                $change = match ($data['adjustment_type']) {
                                    'add' => (int) $data['quantity'],
                                    'remove' => -(int) $data['quantity'],
                                    'set' => (int) $data['quantity'] - ($productSize->stock ?? 0),
                                };

                                $service->adjustStock(
                                    product: $record,
                                    quantityChange: $change,
                                    type: 'adjustment',
                                    reason: $data['reason'],
                                    productSize: $productSize,
                                    user: $user,
                                );
                            } else {
                                $change = match ($data['adjustment_type']) {
                                    'add' => (int) $data['quantity'],
                                    'remove' => -(int) $data['quantity'],
                                    'set' => (int) $data['quantity'] - ($record->quantity ?? 0),
                                };

                                $service->adjustStock(
                                    product: $record,
                                    quantityChange: $change,
                                    type: 'adjustment',
                                    reason: $data['reason'],
                                    user: $user,
                                );
                            }

                            Notification::make()
                                ->title('Stock adjusted successfully')
                                ->success()
                                ->send();
                        })
                        ->modalWidth('lg'),
                ]),
            ]);
    }
}
