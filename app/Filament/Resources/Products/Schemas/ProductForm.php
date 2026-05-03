<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Size;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(array(
                Section::make('Product Details')
                    ->description('Enter the product details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, $set) {
                                $set('slug', Str::slug($state ?? ''));
                            }),
                        Select::make('category_id')
                            ->relationship('category', 'name', modifyQueryUsing: function ($query) {
                                $query->where('is_active', true);
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('slug')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->required(),
                        FileUpload::make('image')
                            ->disk('public')
                            ->directory('products'),
                        RichEditor::make('description')
                            ->extraInputAttributes(array('style' => 'min-height: 15rem'))
                            ->columnSpanFull(),
                    ]),

                Section::make('Sizes')
                    ->description('Add sizes and their additional prices')
                    ->schema([
                        Repeater::make('sizes')
                            ->relationship('sizes')
                            ->schema([
                                Select::make('size_id')
                                    ->options(Size::all()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                TextInput::make('additional_price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->placeholder('0.00'),

                                TextInput::make('stock')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                            ])
                            ->itemLabel(fn(array $state): ?string => isset($state['size_id']) && is_numeric($state['size_id'])
                                ? Size::find($state['size_id'])?->name
                                : 'New Size'
                            )
                            ->collapsible(),
                    ])
                    ->collapsible(),

                Section::make('Price & Quantity')
                    ->description('Set the price and quantity of the product')
                    ->schema([
                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('compare_price')
                            ->numeric()
                            ->prefix('$')
                            ->nullable()
                            ->default(0),
                        TextInput::make('quantity')
                            ->numeric(),
                        Toggle::make('is_featured')
                            ->required(),
                    ]),


            ));
    }
}
