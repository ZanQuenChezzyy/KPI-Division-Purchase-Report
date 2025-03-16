<?php

namespace App\Filament\Exports;

use App\Models\PurchaseRequisition;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Contracts\Database\Eloquent\Builder;

class PurchaseRequisitionExporter extends Exporter
{
    protected static ?string $model = PurchaseRequisition::class;

    public static function getEloquentQuery(): Builder
    {
        return PurchaseRequisition::query()->with([
            'purchaseRequisitionItems.item',
            'purchaseType',
            'department',
            'userDepartment.user'
        ]);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('number')
                ->label('number'),

            ExportColumn::make('purchase_type_id')
                ->label('purchase_type_id')
                ->formatStateUsing(fn(PurchaseRequisition $record) => "{$record->purchaseType->id}.{$record->purchaseType->name}"),

            ExportColumn::make('description')
                ->label('description'),

            ExportColumn::make('requested_by')
                ->label('requested_by')
                ->formatStateUsing(fn(PurchaseRequisition $record) => $record->userDepartment->user->name ?? ''),

            ExportColumn::make('department_id')
                ->label('department_id')
                ->formatStateUsing(fn(PurchaseRequisition $record) => $record->department->name ?? ''),

            ExportColumn::make('status')
                ->label('status'),

            ExportColumn::make('items')
                ->label('items')
                ->formatStateUsing(fn(PurchaseRequisition $record) => self::formatItems($record)),
        ];
    }

    private static function formatItems(PurchaseRequisition $record): string
    {
        $items = collect($record->purchaseRequisitionItems ?? []);

        if ($items->isEmpty()) {
            return '';
        }

        $formattedItems = $items->map(fn($item) => [
            'name' => optional($item->item)->name,
            'qty' => $item->qty ?? 1,
            'unit_price' => $item->unit_price ?? 0
        ]);

        if ($formattedItems->count() === 1) {
            return $formattedItems->first()['name'];
        }

        return json_encode(
            $formattedItems->map(fn($item) => [
                'name' => $item['name'],
                'qty' => $item['qty'],
                ...($item['unit_price'] > 0 ? ['unit_price' => $item['unit_price']] : [])
            ]),
            JSON_UNESCAPED_SLASHES
        );
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your purchase requisition export has completed and '
            . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
