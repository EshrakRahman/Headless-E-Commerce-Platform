<?php

namespace App\Filament\Resources\Inventory\Pages;

use App\Filament\Resources\Inventory\InventoryResource;
use App\Models\ProductSize;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewInventory extends ViewRecord
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('adjustStock')
                ->label('Adjust Stock')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form(function () {
                    $record = $this->getRecord();
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
                ->action(function (array $data) {
                    $record = $this->getRecord();
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
        ];
    }
}
