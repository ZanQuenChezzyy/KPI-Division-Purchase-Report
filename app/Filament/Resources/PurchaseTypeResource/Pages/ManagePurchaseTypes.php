<?php

namespace App\Filament\Resources\PurchaseTypeResource\Pages;

use App\Filament\Resources\PurchaseTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePurchaseTypes extends ManageRecords
{
    protected static string $resource = PurchaseTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Purchase Type')
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
