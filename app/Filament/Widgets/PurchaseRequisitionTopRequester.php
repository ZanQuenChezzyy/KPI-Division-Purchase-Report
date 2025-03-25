<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseRequisition;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PurchaseRequisitionTopRequester extends BaseWidget
{
    protected static ?string $heading = 'Top Requesters';
    protected int|string|array $columnSpan = '2';
    protected static ?int $sort = 8;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Top Requesters')
            ->description('A list of Requester ranked by the number of purchase requisitions they have made.')
            ->query(
                PurchaseRequisition::query()
                    ->join('users', 'purchase_requisitions.requested_by', '=', 'users.id') // Hapus user_departments
                    ->selectRaw('users.id, users.name as requester_name, COUNT(purchase_requisitions.id) as total_requests')
                    ->groupBy('users.id', 'users.name')
                    ->orderByDesc('total_requests')
            )
            ->columns([
                TextColumn::make('index')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('requester_name')
                    ->label('Requester Name'),
                TextColumn::make('total_requests')
                    ->label('Total PRs'),
            ])
            ->defaultSort('total_requests', 'desc')
            ->defaultPaginationPageOption(5);
    }
}
