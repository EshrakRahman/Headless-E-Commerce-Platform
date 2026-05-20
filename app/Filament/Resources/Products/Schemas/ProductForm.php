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
            ->components([
                Section::make('Product Details')
                    ->description('Enter the product details')
                    ->schema([
                        static::getNameField(),
                        static::getCategoryField(),
                        static::getSlugField(),
                        static::getImageField(),
                        static::getDescriptionField(),
                    ]),

                Section::make('Sizes')
                    ->description('Add sizes and their additional prices')
                    ->schema([
                        static::getSizesRepeater(),
                    ])
                    ->collapsible(),

                Section::make('Price & Quantity')
                    ->description('Set the price and quantity of the product')
                    ->schema([
                        static::getPriceField(),
                        static::getComparePriceField(),
                        static::getQuantityField(),
                        static::getFeaturedField(),
                    ]),
            ]);
    }

    public static function getNameField(): TextInput
    {
        return TextInput::make('name')
            ->required()
            ->live(onBlur: true)
            ->afterStateUpdated(function (?string $state, $set) {
                $set('slug', Str::slug($state ?? ''));
            });
    }

    public static function getCategoryField(): Select
    {
        return Select::make('category_id')
            ->relationship('category', 'name', modifyQueryUsing: function ($query) {
                $query->where('is_active', true);
            })
            ->searchable()
            ->preload()
            ->required();
    }

    public static function getSlugField(): TextInput
    {
        return TextInput::make('slug')
            ->unique(ignoreRecord: true)
            ->maxLength(255)
            ->required();
    }

    public static function getImageField(): FileUpload
    {
        return FileUpload::make('image')
            ->disk('s3')
            ->directory('products');
    }

    public static function getDescriptionField(): RichEditor
    {
        return RichEditor::make('description')
            ->extraInputAttributes(['style' => 'min-height: 15rem'])
            ->columnSpanFull();
    }

    public static function getSizesRepeater(): Repeater
    {
        return Repeater::make('productSizes')
            ->relationship()
            ->schema([
                Select::make('size_id')
                    ->relationship('size', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
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
                    ->required(),
            ])
            ->itemLabel(fn (array $state): ?string => isset($state['size_id'])
                ? Size::find($state['size_id'])?->name
                : 'New Size'
            )
            ->collapsible()
            ->cloneable()
            ->defaultItems(0);
    }

    public static function getPriceField(): TextInput
    {
        return TextInput::make('price')
            ->required()
            ->numeric()
            ->prefix('$');
    }

    public static function getComparePriceField(): TextInput
    {
        return TextInput::make('compare_price')
            ->numeric()
            ->prefix('$')
            ->nullable()
            ->default(0);
    }

    public static function getQuantityField(): TextInput
    {
        return TextInput::make('quantity')
            ->numeric()
            ->helperText('Used when product has no sizes. For sized products, stock is tracked per size.');
    }

    public static function getFeaturedField(): Toggle
    {
        return Toggle::make('is_featured')
            ->required();
    }
}
