<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseRequisitionResource\Pages;
use App\Filament\Resources\PurchaseRequisitionResource\RelationManagers;
use App\Models\PurchaseRequisition;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PurchaseRequisitionResource extends Resource
{
    protected static ?string $model = PurchaseRequisition::class;
    protected static ?string $label = 'Purchase Requisition';
    protected static ?string $navigationGroup = 'Manage Purchase';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $activeNavigationIcon = 'heroicon-s-clipboard-document-check';
    protected static ?int $navigationSort = 2;
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() < 2 ? 'danger' : 'info';
    }
    protected static ?string $navigationBadgeTooltip = 'Total Purchase Requisition';
    protected static ?string $slug = 'purchase-requisition';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('General Information')
                            ->schema([
                                Forms\Components\TextInput::make('number')
                                    ->required()
                                    ->numeric(),
                                Forms\Components\Select::make('purchase_type_id')
                                    ->relationship('purchaseType', 'name')
                                    ->required(),
                                Forms\Components\Textarea::make('description')
                                    ->required()
                                    ->columnSpanFull(),
                            ])->columns(2)
                            ->columnSpan(1),
                        Section::make('Requester Details')
                            ->schema([
                                Forms\Components\TextInput::make('requested_by')
                                    ->required()
                                    ->maxLength(45),
                                Forms\Components\Select::make('department_id')
                                    ->relationship('department', 'name')
                                    ->required(),
                            ])->columns(2)
                            ->columnSpan(1),
                    ]),
                Group::make()
                    ->schema([
                        Section::make('Status & Approval')
                            ->schema([
                                Forms\Components\TextInput::make('status')
                                    ->required()
                                    ->numeric()
                                    ->columnSpanFull()
                                    ->default(0),
                                Forms\Components\DatePicker::make('approved_at'),
                                Forms\Components\DatePicker::make('cancelled_at'),
                            ])->columns(2)
                            ->columnSpan(1),
                        Section::make('Purchase Items')
                            ->schema([
                                Repeater::make('Items')
                                    ->relationship('purchaseRequisitionItems')
                                    ->schema([
                                        Select::make('item_id')
                                            ->label('Item')
                                            ->placeholder('Select Item')
                                            ->relationship('Item', 'name')
                                            ->native(false)
                                            ->preload()
                                            ->searchable()
                                            ->reactive() // Aktifkan reactive untuk mendeteksi perubahan
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                // Ambil harga satuan berdasarkan item_id yang dipilih
                                                $unitPrice = \App\Models\Item::find($state)?->unit_price ?? 0;
                                                $set('unit_price', $unitPrice); // Set nilai unit_price
                                            })
                                            ->columnSpan(3)
                                            ->required(),
                                        TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->hidden()
                                            ->numeric(),
                                        TextInput::make('qty')
                                            ->label('Quantity')
                                            ->placeholder('Qty')
                                            ->minValue(1)
                                            ->maxValue(999)
                                            ->minLength(1)
                                            ->maxLength(3)
                                            ->numeric()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                $unitPrice = $get('unit_price') ?? 0;
                                                $totalPrice = $unitPrice * $state;

                                                $formattedTotalPrice = number_format($totalPrice, 0, '.', ',');

                                                $set('total_price', $formattedTotalPrice);
                                            })
                                            ->columnSpan(1)
                                            ->required(),
                                        TextInput::make('total_price')
                                            ->label('Total Price')
                                            ->mask(RawJs::make('$money($input)'))
                                            ->stripCharacters(',')
                                            ->columnSpan(2)
                                            ->disabled()
                                            ->numeric()
                                            ->dehydrated()
                                            ->required(),
                                    ])->columns(6),
                            ])
                    ]),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseType.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('requested_by')
                    ->searchable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cancelled_at')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseRequisitions::route('/'),
            'create' => Pages\CreatePurchaseRequisition::route('/create'),
            'view' => Pages\ViewPurchaseRequisition::route('/{record}'),
            'edit' => Pages\EditPurchaseRequisition::route('/{record}/edit'),
        ];
    }
}
