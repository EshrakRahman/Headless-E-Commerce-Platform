<?php

namespace App\Filament\Resources\Discounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Discount Details')
                    ->columns(1)
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Select::make('type')
                            ->options([
                                'percentage' => 'Percentage (%)',
                                'fixed' => 'Fixed Amount ($)',
                            ])
                            ->required()
                            ->native(false)
                            ->live(),

                        TextInput::make('value')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->prefix(fn ($get) => $get('type') === 'percentage' ? '%' : '$'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),

                Section::make('Schedule')
                    ->columns(1)
                    ->columnSpan(1)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Starts At')
                            ->nullable()
                            ->native(false),

                        DateTimePicker::make('ends_at')
                            ->label('Ends At')
                            ->after('starts_at')
                            ->nullable()
                            ->native(false),
                    ]),

                Section::make('Products')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('products')
                            ->multiple()
                            ->relationship('products', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
            ]);
    }
}
