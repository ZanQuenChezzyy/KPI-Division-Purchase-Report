<?php

namespace App\Filament\Resources\PurchaseRequisitionResource\Pages;

use App\Filament\Resources\PurchaseRequisitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Actions\ImportAction;

class ViewPurchaseRequisition extends ViewRecord
{
    protected static string $resource = PurchaseRequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Purchase Requisition')
                ->icon('heroicon-s-pencil-square')
                ->color('info'),
        ];
    }
}
