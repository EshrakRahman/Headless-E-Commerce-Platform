<?php

namespace App\Filament\Resources\Inventory\Schemas;

use App\Models\ProductSize;
use App\Services\InventoryService;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;

class InventoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('category.name')
                            ->label('Category'),
                        TextEntry::make('slug'),
                        TextEntry::make('price')
                            ->money(),
                    ]),

                Section::make('Current Stock')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('current_stock')
                            ->label('Total Stock')
                            ->getStateUsing(function ($record) {
                                if ($record->sizes()->exists()) {
                                    return $record->sizes()->sum('product_size.stock');
                                }

                                return $record->quantity ?? 0;
                            })
                            ->badge()
                            ->color(fn ($state): string => match (true) {
                                $state > 10 => 'success',
                                $state > 0 && $state <= 10 => 'warning',
                                default => 'danger',
                            }),

                        TextEntry::make('stock_type')
                            ->label('Stock Type')
                            ->getStateUsing(fn ($record) => $record->sizes()->exists() ? 'Sized Product' : 'Simple Product')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'Sized Product' ? 'info' : 'gray'),
                    ]),

                Section::make('Stock Breakdown')
                    ->visible(fn ($record) => $record->sizes()->exists())
                    ->schema([
                        RepeatableEntry::make('sizes')
                            ->label('Per-Size Stock')
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('pivot.stock')
                                    ->label('Stock')
                                    ->numeric(),
                            ])
                            ->columns(2),
                    ]),

                Section::make('Stock Movement History')
                    ->schema([
                        RepeatableEntry::make('stock_movements')
                            ->label('Movements')
                            ->relationship('stockMovements')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Date')
                                    ->dateTime('M j, Y H:i'),
                                TextEntry::make('type')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'initial' => 'gray',
                                        'adjustment' => 'warning',
                                        'order' => 'danger',
                                        'refund' => 'success',
                                        default => 'gray',
                                    }),
                                TextEntry::make('quantity_change')
                                    ->label('Change')
                                    ->formatStateUsing(fn ($state): string => $state > 0 ? '+'.$state : (string) $state)
                                    ->color(fn ($state): string => $state > 0 ? 'success' : 'danger'),
                                TextEntry::make('before_quantity')
                                    ->label('Before'),
                                TextEntry::make('after_quantity')
                                    ->label('After'),
                                TextEntry::make('reason')
                                    ->placeholder('-'),
                                TextEntry::make('user.name')
                                    ->label('By')
                                    ->placeholder('System'),
                            ])
                            ->columns(3)
                            ->grid()
                            ->defaultSort('created_at', 'desc'),
                    ]),

                Section::make('Quick Actions')
                    ->schema([
                        Actions::make([
                            Actions\Action::make('adjustStock')
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
                                        ->label('Type')
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
                    ]),
            ]);
    }
}
