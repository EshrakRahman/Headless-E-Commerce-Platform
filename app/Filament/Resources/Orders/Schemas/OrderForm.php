<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()->tabs([
                    Tab::make('Order Details')
                        ->schema([
                            TextInput::make('order_number')
                                ->disabled()
                                ->dehydrated(false),
                            Placeholder::make('user')
                                ->label('Customer')
                                ->content(fn ($record) => $record?->user?->name),
                            Select::make('status')
                                ->options(OrderStatus::class)
                                ->required()
                                ->native(false),
                            Select::make('payment_status')
                                ->options(PaymentStatus::class)
                                ->required()
                                ->native(false),
                            Textarea::make('notes')
                                ->columnSpanFull(),
                        ]),

                    Tab::make('Items')
                        ->schema([
                            Placeholder::make('items_list')
                                ->label('Order Items')
                                ->content(function ($record) {
                                    if (! $record?->items->count()) {
                                        return 'No items';
                                    }

                                    $html = '<table class="w-full text-left text-sm"><thead><tr>';
                                    $html .= '<th class="p-2">Product</th><th class="p-2">Size</th><th class="p-2">Qty</th><th class="p-2">Price</th><th class="p-2">Subtotal</th>';
                                    $html .= '</tr></thead><tbody>';

                                    foreach ($record->items as $item) {
                                        $html .= '<tr class="border-t">';
                                        $html .= '<td class="p-2">'.e($item->product_name).'</td>';
                                        $html .= '<td class="p-2">'.e($item->size_name ?? '-').'</td>';
                                        $html .= '<td class="p-2">'.$item->quantity.'</td>';
                                        $html .= '<td class="p-2">$'.number_format($item->unit_price, 2).'</td>';
                                        $html .= '<td class="p-2">$'.number_format($item->subtotal, 2).'</td>';
                                        $html .= '</tr>';
                                    }

                                    $html .= '</tbody></table>';

                                    return $html;
                                })
                                ->html(),
                        ]),

                    Tab::make('Addresses')
                        ->schema([
                            Section::make('Shipping Address')
                                ->schema([
                                    KeyValue::make('shipping_address')
                                        ->keyLabel('Field')
                                        ->valueLabel('Value')
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),
                            Section::make('Billing Address')
                                ->schema([
                                    KeyValue::make('billing_address')
                                        ->keyLabel('Field')
                                        ->valueLabel('Value')
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),
                        ]),
                ]),
            ]);
    }
}
