<?php

namespace App\Filament\Resources\Orders\Pages;

use App\DTOs\OrderData;
use App\DTOs\OrderItemData;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Customer')
                    ->description('Select the customer for this order')
                    ->icon('heroicon-o-user')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                Section::make('Notes')
                    ->description('Additional order notes')
                    ->icon('heroicon-o-document-text')
                    ->columnSpan(1)
                    ->schema([
                        Textarea::make('notes')
                            ->rows(4),
                    ]),

                Section::make('Order Items')
                    ->description('Add the products being ordered')
                    ->icon('heroicon-o-shopping-cart')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->columns(4)
                            ->columnSpanFull()
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->options(Product::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($set) {
                                        $set('size_id', null);
                                    })
                                    ->columnSpan(2),

                                Select::make('size_id')
                                    ->label('Size')
                                    ->options(fn ($get) => $get('product_id')
                                        ? Product::find($get('product_id'))?->sizes->pluck('name', 'id') ?? []
                                        : []
                                    )
                                    ->searchable()
                                    ->nullable()
                                    ->live(),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),
                            ])
                            ->defaultItems(1)
                            ->minItems(1)
                            ->reorderable()
                            ->addActionLabel('Add another item'),
                    ]),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::findOrFail($data['user_id']);

        $items = array_map(
            fn (array $item): OrderItemData => OrderItemData::fromArray($item),
            $data['items']
        );

        $orderData = new OrderData(
            items: $items,
            shippingAddress: null,
            billingAddress: null,
            notes: $data['notes'] ?? null,
            couponCode: null,
            paymentMethod: 'cash'
        );

        return app(OrderService::class)->createOrder($user, $orderData);
    }
}
