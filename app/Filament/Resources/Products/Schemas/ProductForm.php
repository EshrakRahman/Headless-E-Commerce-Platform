<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->extraInputAttributes(['style' => 'min-height: 15rem'])
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('quantity')
                    ->numeric(),
                Toggle::make('is_featured')
                    ->required(),
            ]);
    }
}
