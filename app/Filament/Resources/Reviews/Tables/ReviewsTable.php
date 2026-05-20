<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Models\Review;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable(),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->sortable(),

                TextColumn::make('rating')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state)),

                TextColumn::make('title')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('body')
                    ->searchable()
                    ->limit(50),

                IconColumn::make('is_approved')
                    ->label('Approved')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->date('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_approved')
                    ->label('Status')
                    ->options([
                        '0' => 'Pending',
                        '1' => 'Approved',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),

                    Action::make('approve')
                        ->label('Approve')
                        ->icon(Heroicon::OutlinedCheck)
                        ->color('success')
                        ->visible(fn (Review $record): bool => ! $record->is_approved)
                        ->action(fn (Review $record) => $record->update(['is_approved' => true])),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon(Heroicon::OutlinedXMark)
                        ->color('danger')
                        ->visible(fn (Review $record): bool => $record->is_approved)
                        ->action(fn (Review $record) => $record->update(['is_approved' => false])),
                ]),
            ])
            ->bulkActions([
                BulkAction::make('approve_selected')
                    ->label('Approve Selected')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->action(fn (Collection $records) => $records->each->update(['is_approved' => true]))
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('reject_selected')
                    ->label('Reject Selected')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->action(fn (Collection $records) => $records->each->update(['is_approved' => false]))
                    ->deselectRecordsAfterCompletion(),

                DeleteAction::make(),
            ]);
    }
}
