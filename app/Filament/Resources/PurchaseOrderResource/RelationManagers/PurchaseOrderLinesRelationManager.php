<?php

namespace App\Filament\Resources\PurchaseOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PurchaseOrderLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseOrderLines';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('item_id')
                    ->relationship('item', 'name')
                    ->required(),
                Forms\Components\TextInput::make('qty')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('unit_price')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('tax')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('total_price')
                    ->numeric(),
                Forms\Components\TextInput::make('received_qty')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item_id')
            ->columns([
                TextColumn::make('item.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('qty')
                    ->label('Qty')
                    ->prefix('x ')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_price')
                    ->numeric()
                    ->sortable(),
                TextInputColumn::make('received_qty')
                    ->label('Received Qty')
                    ->width('1%')
                    ->rules(fn($record) => ['required', 'min:0', 'numeric', 'max:' . $record->qty . ''])
                    ->type('number')
                    ->afterStateUpdated(function ($state, $record) {
                        // Update status PurchaseOrderLine
                        $newStatus = $state >= $record->qty ? 2 : 1; // 2 = Received, 1 = Partial Received
                        $record->update(['status' => $newStatus]);

                        // Cek semua PurchaseOrderLine dalam PurchaseOrder
                        $allLinesReceived = $record->PurchaseOrder->purchaseOrderLines->every(function ($line) {
                            return $line->status == 2; // Semua harus berstatus 'Received'
                        });

                        // Update PurchaseOrder berdasarkan hasil pengecekan
                        if ($allLinesReceived) {
                            $record->PurchaseOrder->update([
                                'is_received' => true,
                                'received_at' => now(),
                            ]);
                        } else {
                            $record->PurchaseOrder->update([
                                'is_received' => false,
                                'received_at' => null,
                            ]);
                        }
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state): string => match ($state) {
                        0 => 'danger',
                        1 => 'warning',
                        2 => 'success',
                    })
                    ->formatStateUsing(fn($state): string => match ($state) {
                        0 => 'Pending',
                        1 => 'Partial Received',
                        2 => 'Received',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->hidden(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
