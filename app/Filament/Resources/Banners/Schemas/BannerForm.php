<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Content')
                    ->columns(1)
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('subtitle')
                            ->nullable()
                            ->maxLength(255),

                        TextInput::make('cta_text')
                            ->label('CTA Text')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('cta_url')
                            ->label('CTA URL')
                            ->required()
                            ->maxLength(500),

                        Select::make('text_color')
                            ->label('Text Color Theme')
                            ->options([
                                'light' => 'Light',
                                'dark' => 'Dark',
                            ])
                            ->nullable(),
                    ]),

                Section::make('Media & Styling')
                    ->columns(1)
                    ->columnSpan(1)
                    ->schema([
                        FileUpload::make('desktop_image')
                            ->label('Desktop Image')
                            ->disk('s3')
                            ->image()
                            ->directory('banners')
                            ->visibility('public')
                            ->nullable(),

                        FileUpload::make('mobile_image')
                            ->label('Mobile Image')
                            ->disk('s3')
                            ->image()
                            ->directory('banners')
                            ->visibility('public')
                            ->nullable(),

                        ColorPicker::make('bg_color')
                            ->label('Background Color')
                            ->nullable(),
                    ]),

                Section::make('Settings')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('position')
                            ->options([
                                'hero' => 'Hero',
                            ])
                            ->default('hero')
                            ->required(),

                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        DateTimePicker::make('starts_at')
                            ->label('Starts At')
                            ->nullable(),

                        DateTimePicker::make('ends_at')
                            ->label('Ends At')
                            ->nullable(),
                    ]),
            ]);
    }
}
