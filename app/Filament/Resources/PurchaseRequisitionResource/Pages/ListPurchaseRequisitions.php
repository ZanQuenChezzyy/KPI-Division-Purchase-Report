<?php

namespace App\Filament\Resources\PurchaseRequisitionResource\Pages;

use App\Filament\Imports\PurchaseRequisitionImporter;
use App\Filament\Resources\PurchaseRequisitionResource;
use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseRequisitions extends ListRecords
{
    protected static string $resource = PurchaseRequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(PurchaseRequisitionImporter::class),
            Actions\CreateAction::make()
                ->label('Create Purchase Requisition')
                ->icon('heroicon-s-plus-circle'),
        ];
    }
}
