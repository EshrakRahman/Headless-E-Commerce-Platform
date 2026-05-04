<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected array $calculatedItemsData = [];

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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $itemsData = [];
        $total = 0;

        foreach ($data['items'] as $item) {
            $product = Product::with('sizes')->findOrFail($item['product_id']);
            $unitPrice = (float) $product->price;
            $sizeName = null;

            if (! empty($item['size_id'])) {
                $size = $product->sizes()
                    ->where('size_id', $item['size_id'])
                    ->first();

                if (! $size) {
                    throw ValidationException::withMessages([
                        'items.*.size_id' => "Size is not available for product '{$product->name}'.",
                    ]);
                }

                /** @var ProductSize $pivot */
                $pivot = $size->pivot;

                if ($pivot->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items.*.quantity' => "Not enough stock for {$product->name} - {$size->name}. Available: {$pivot->stock}.",
                    ]);
                }

                $unitPrice += (float) $pivot->additional_price;
                $sizeName = $size->name;

                $pivot->decrement('stock', $item['quantity']);
            } else {
                if ($product->quantity < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items.*.quantity' => "Not enough stock for '{$product->name}'. Available: {$product->quantity}.",
                    ]);
                }

                $product->decrement('quantity', $item['quantity']);
            }

            $subtotal = $unitPrice * $item['quantity'];
            $total += $subtotal;

            $itemsData[] = [
                'product_id' => $product->id,
                'size_id' => $item['size_id'] ?? null,
                'product_name' => $product->name,
                'size_name' => $sizeName,
                'unit_price' => $unitPrice,
                'quantity' => $item['quantity'],
                'subtotal' => $subtotal,
            ];
        }

        $this->calculatedItemsData = $itemsData;

        $lastId = Order::max('id') ?? 0;

        unset($data['items']);

        $data['order_number'] = 'ORD-'.now()->format('Ymd').'-'.str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        $data['status'] = 'pending';
        $data['subtotal'] = $total;
        $data['shipping_cost'] = 0;
        $data['discount'] = 0;
        $data['total'] = $total;
        $data['payment_method'] = null;
        $data['payment_status'] = 'pending';
        $data['shipping_address'] = null;
        $data['billing_address'] = null;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->items()->createMany($this->calculatedItemsData);
    }
}
