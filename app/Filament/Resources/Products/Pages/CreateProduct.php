<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Schemas\ProductForm;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Wizard\Step;

class CreateProduct extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = ProductResource::class;

    protected function getSteps(): array
    {
        return [
            Step::make('Product Details')
                ->description('Enter the basic product information')
                ->schema([
                    ProductForm::getNameField(),
                    ProductForm::getCategoryField(),
                    ProductForm::getSlugField(),
                    ProductForm::getImageField(),
                    ProductForm::getDescriptionField(),
                ]),

            Step::make('Sizes')
                ->description('Add size variants with pricing and stock')
                ->schema([
                    ProductForm::getSizesRepeater(),
                ]),

            Step::make('Pricing')
                ->description('Set pricing, discounts and inventory')
                ->schema([
                    ProductForm::getPriceField(),
                    ProductForm::getComparePriceField(),
                    ProductForm::getQuantityField(),
                    ProductForm::getFeaturedField(),
                ]),
        ];
    }
}
