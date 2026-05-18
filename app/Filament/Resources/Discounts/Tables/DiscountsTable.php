<?php

namespace App\Filament\Resources\Discounts\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DiscountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state?->value === 'percentage' ? '% Off' : '$ Off')
                    ->color(fn (mixed $state): string => $state?->value === 'percentage' ? 'info' : 'warning'),

                TextColumn::make('value')
                    ->money()
                    ->formatStateUsing(fn ($record) => $record->type === 'percentage'
                        ? $record->value.'%'
                        : '$'.number_format($record->value, 2)
                    ),

                TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->badge(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label('Starts')
                    ->date('M j, Y')
                    ->placeholder('Immediately')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Ends')
                    ->date('M j, Y')
                    ->placeholder('No end')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed',
                    ]),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
