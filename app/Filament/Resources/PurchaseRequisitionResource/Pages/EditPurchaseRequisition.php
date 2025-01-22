<?php

namespace App\Filament\Resources\PurchaseRequisitionResource\Pages;

use App\Filament\Resources\PurchaseRequisitionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseRequisition extends EditRecord
{
    protected static string $resource = PurchaseRequisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('View Purchase Requisition')
                ->icon('heroicon-s-eye'),
            Actions\DeleteAction::make()
                ->label('Delete Purchase Requisition')
                ->icon('heroicon-s-trash'),
        ];
    }
}
