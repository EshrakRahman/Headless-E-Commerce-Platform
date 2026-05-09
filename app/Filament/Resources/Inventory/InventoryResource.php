<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\Pages\ListInventories;
use App\Filament\Resources\Inventory\Pages\ViewInventory;
use App\Filament\Resources\Inventory\Schemas\InventoryInfolist;
use App\Filament\Resources\Inventory\Tables\InventoriesTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InventoryResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $navigationLabel = 'Inventory';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $pluralModelLabel = 'Inventory';

    protected static ?string $slug = 'inventory';

    public static function infolist(Schema $schema): Schema
    {
        return InventoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventories::route('/'),
            'view' => ViewInventory::route('/{record}'),
        ];
    }
}
