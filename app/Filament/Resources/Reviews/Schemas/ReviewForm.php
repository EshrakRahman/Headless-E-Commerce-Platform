<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Review Details')
                    ->schema([
                        TextInput::make('rating')
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(5),

                        TextInput::make('title')
                            ->nullable()
                            ->maxLength(255),

                        Textarea::make('body')
                            ->required()
                            ->maxLength(2000)
                            ->rows(4),

                        Toggle::make('is_approved')
                            ->label('Approved')
                            ->default(false),
                    ]),
            ]);
    }
}
