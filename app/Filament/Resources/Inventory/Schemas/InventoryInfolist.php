<?php

namespace App\Filament\Resources\Inventory\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InventoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Information')
                    ->columns()
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
                            ->columns(3),
                    ]),

            ]);
    }
}
