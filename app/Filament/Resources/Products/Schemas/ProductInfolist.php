<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Resources\Inventory\InventoryResource;
use App\Models\Product;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('category.name')
                    ->label('Category'),
                TextEntry::make('name'),
                TextEntry::make('slug'),
                ImageEntry::make('image')
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('price')
                    ->money(),
                TextEntry::make('compare_price')
                    ->money()
                    ->placeholder('-'),
                TextEntry::make('quantity')
                    ->numeric()
                    ->placeholder('-'),
                RepeatableEntry::make('sizes')
                    ->label('Sizes')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('pivot.additional_price')
                            ->label('Additional Price')
                            ->money(),
                        TextEntry::make('pivot.stock')
                            ->label('Stock')
                            ->numeric(),
                    ])
                    ->columns(3)
                    ->placeholder('No sizes'),
                IconEntry::make('is_featured')
                    ->boolean(),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Product $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),

                Section::make('Inventory')
                    ->schema([
                        TextEntry::make('stock_summary')
                            ->label('Current Stock')
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
                        TextEntry::make('inventory_link')
                            ->label('')
                            ->getStateUsing(fn () => 'View Full Inventory Details →')
                            ->url(fn (Product $record) => InventoryResource::getUrl('view', ['record' => $record]))
                            ->icon('heroicon-o-cube'),
                    ]),
            ]);
    }
}
