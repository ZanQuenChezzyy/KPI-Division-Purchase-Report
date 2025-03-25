<?php

namespace App\Filament\Resources\PurchaseRequisitionResource\Pages;

use App\Filament\Exports\PurchaseRequisitionExporter;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;

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
                ->label('Import')
                ->icon('heroicon-s-document-arrow-down')
                ->importer(PurchaseRequisitionImporter::class),
            // ExportAction::make()
            //     ->exporter(PurchaseRequisitionExporter::class)
            //     ->label('Export')
            //     ->icon('heroicon-s-document-arrow-up')
            //     ->formats([
            //         ExportFormat::Xlsx,
            //         ExportFormat::Csv,
            //     ]),
            Actions\CreateAction::make()
                ->label('Create Purchase Requisition')
                ->icon('heroicon-s-plus-circle'),
        ];
    }
}
