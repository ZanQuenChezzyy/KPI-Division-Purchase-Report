<?php

namespace App\Filament\Resources\PurchaseOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Group;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\RawJs;
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
                Fieldset::make('Purchase Order Lines Details')
                    ->schema([
                        Group::make([
                            Forms\Components\TextInput::make('qty')
                                ->label(fn($record): string => $record->item->name . ' Quantity' ?? 'No Item')
                                ->placeholder('Enter Quantity')
                                ->prefix('Quantity')
                                ->disabled()
                                ->dehydrated()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $unitPrice = (float) str_replace(',', '', $get('unit_price')) ?? 0; // Bersihkan koma
                                    $qty = (int) ($state ?? 0);
                                    $totalPrice = $unitPrice * $qty;
                                    $formattedTotalPrice = number_format($totalPrice, 0, '.', ',');
                                    $set('total_price', $formattedTotalPrice);
                                })
                                ->numeric()
                                ->required(),
                            Forms\Components\TextInput::make('unit_price')
                                ->label(fn($record): string => $record->item->name . ' Unit Price' ?? 'No Item')
                                ->placeholder('Enter Unit Price')
                                ->mask(RawJs::make('$money($input)'))
                                ->stripCharacters(',')
                                ->prefix('Rp')
                                ->suffix('.00')
                                ->minValue(1000)
                                ->minLength(4)
                                ->maxLength(20)
                                ->live(debounce: 1000)
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $qty = (int) ($get('qty') ?? 0);
                                    $unitPrice = (float) str_replace(',', '', $state) ?? 0; // Bersihkan koma
                                    $totalPrice = $unitPrice * $qty;
                                    $formattedTotalPrice = number_format($totalPrice, 0, '.', ',');
                                    $set('total_price', $formattedTotalPrice);
                                })
                                ->numeric()
                                ->required(),
                            Forms\Components\TextInput::make('received_qty')
                                ->prefix('Quantity')
                                ->placeholder('Enter Received Quantity')
                                ->minValue(0)
                                ->maxValue(fn($record) => $record->qty)
                                ->default(0)
                                ->live(debounce: 1000)
                                ->afterStateUpdated(function ($state, $record, callable $get, callable $set) {
                                    // Tentukan status baru berdasarkan received_qty
                                    $newStatus = 0; // Default: Pending

                                    if ($state == 0) {
                                        $newStatus = 0; // Pending
                                    } elseif ($state >= $record->qty) {
                                        $newStatus = 2; // Received
                                    } else {
                                        $newStatus = 1; // Partial Received
                                    }

                                    // Update status di database untuk baris ini
                                    $record->update(['status' => $newStatus]);

                                    // Update kolom 'status' di form
                                    $set('status', $newStatus);

                                    // Cek apakah semua baris dalam PurchaseOrder sudah 'Received'
                                    $allLinesReceived = $record->PurchaseOrder->purchaseOrderLines->every(fn($line) => $line->status == 2);

                                    // Update status 'is_received' di tabel 'purchase_order'
                                    $record->PurchaseOrder->update([
                                        'is_received' => $allLinesReceived,
                                        'received_at' => $allLinesReceived ? now() : null,
                                    ]);
                                })
                                ->numeric()
                                ->required(),
                        ])
                            ->columns(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('total_price')
                            ->label('Total Price')
                            ->placeholder('Enter Price')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->prefix('Rp')
                            ->suffix('.00')
                            ->minValue(1000)
                            ->minLength(4)
                            ->maxLength(20)
                            ->disabled()
                            ->numeric()
                            ->dehydrated(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->placeholder('Select Status')
                            ->options([
                                0 => 'Pending',
                                1 => 'Partial Received',
                                2 => 'Received',
                            ])
                            ->native(false)
                            ->preload()
                            ->searchable()
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('Order Line Description')
                            ->placeholder('Enter Order Line Description')
                            ->minLength(3)
                            ->rows(3)
                            ->autosize()
                            ->columnSpanFull(),
                    ])->columns(2),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn($record): string => $record->item->name ?? 'No Item')
            ->columns([
                TextColumn::make('item.name')
                    ->label('Item')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('qty')
                    ->label('Quantity')
                    ->width('1%')
                    ->prefix('x ')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('received_qty')
                    ->label('Received Quantity')
                    ->width('1%')
                    ->prefix('x ')
                    ->sortable(),
                TextColumn::make('total_price')
                    ->numeric()
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
                    ->button()
                    ->color('primary'),
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
