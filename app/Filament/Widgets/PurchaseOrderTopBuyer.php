<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PurchaseOrderTopBuyer extends BaseWidget
{
    protected static ?string $heading = 'Top Buyers';
    protected int|string|array $columnSpan = '2';
    protected static ?int $sort = 10;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Top Buyers')
            ->description('A list of buyers ranked by the number of purchase orders they have made.')
            ->query(
                PurchaseOrder::query()
                    ->join('users', 'purchase_orders.buyer', '=', 'users.id') // Sesuaikan dengan kolom relasi buyer
                    ->selectRaw('users.id AS id, users.name AS buyer_name, COUNT(purchase_orders.id) AS total_po') // Tambahkan ID
                    ->groupBy('users.id', 'users.name')
                    ->orderByDesc('total_po')
            )
            ->columns([
                TextColumn::make('index')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('buyer_name')
                    ->label('Buyer Name'),
                TextColumn::make('total_po')
                    ->label('Total POs'),
            ])
            ->defaultPaginationPageOption(5);
    }
}
