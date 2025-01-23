<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;
    protected static ?string $label = 'Purchase Orders';
    protected static ?string $navigationGroup = 'Manage Purchase';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $activeNavigationIcon = 'heroicon-s-clipboard-document-list';
    protected static ?int $navigationSort = 3;
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() < 2 ? 'danger' : 'info';
    }
    protected static ?string $navigationBadgeTooltip = 'Total Purchase Orders';
    protected static ?string $slug = 'purchase-orders';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Purchase Information')
                    ->schema([
                        Select::make('purchase_requisition_id')
                            ->label('Purchase Requisition')
                            ->placeholder('Select Purchase Requisition')
                            ->relationship('purchaseRequisition', 'id', fn(Builder $query) => $query->where('status', 2))
                            ->native(false)
                            ->preload()
                            ->columnSpanFull()
                            ->noSearchResultsMessage('No Purchase Requisition found.')
                            ->searchable(['number', 'requested_by'])
                            ->getOptionLabelFromRecordUsing(function (Model $record) {
                                $number = $record->number;
                                $type = $record->purchaseType->name;
                                $requstedBy = $record->requested_by;
                                $department = $record->department->name;

                                return "($number) - $type [$requstedBy, $department]";
                            })
                            ->required(),

                        Select::make('vendor_id')
                            ->label('Vendor')
                            ->placeholder('Select Vendor')
                            ->relationship('vendor', 'name')
                            ->native(false)
                            ->preload()
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(function (Model $record) {
                                $name = $record->name;
                                $type = $record->type;

                                return "$name - $type";
                            })
                            ->required(),

                        TextInput::make('buyer')
                            ->label('Buyer')
                            ->placeholder('Enter Buyer name')
                            ->minLength(3)
                            ->maxLength(45)
                            ->required(),
                    ])->columns(2)
                    ->columnSpan(3),
                Section::make('Order Status')
                    ->schema([
                        Group::make()
                            ->schema([
                                Toggle::make('is_confirmed')
                                    ->label('Order Confirmed')
                                    ->inline(false)
                                    ->onIcon('heroicon-m-bolt')
                                    ->offIcon('heroicon-m-check')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $set('confirmed_at', now());
                                        } else {
                                            $set('confirmed_at', null);
                                        }
                                    }),

                                Toggle::make('is_received')
                                    ->label('Order Received')
                                    ->inline(false)
                                    ->onColor('success')
                                    ->onIcon('heroicon-m-bolt')
                                    ->offIcon('heroicon-m-check')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $set('received_at', now());
                                        } else {
                                            $set('received_at', null);
                                        }
                                    }),

                                Toggle::make('is_closed')
                                    ->label('Order Closed')
                                    ->inline(false)
                                    ->onColor('danger')
                                    ->onIcon('heroicon-m-bolt')
                                    ->offIcon('heroicon-m-x-mark')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $set('closed_at', now());
                                        } else {
                                            $set('closed_at', null);
                                        }
                                    }),
                            ])->columns(3)
                            ->columnSpan(2),
                        Group::make([
                            DatePicker::make('confirmed_at')
                                ->label('Confirmed At')
                                ->placeholder('Select Confirmed Date')
                                ->native(false)
                                ->dehydratedWhenHidden()
                                ->hidden(fn(Get $get): bool => !$get('is_confirmed'))
                                ->columnSpan(fn(Get $get): ?string => $get('is_confirmed') && (!$get('is_received') && !$get('is_closed')) ? 'full' : null)
                                ->required(fn(Get $get): bool => $get('is_confirmed')),

                            DatePicker::make('received_at')
                                ->label('Received At')
                                ->placeholder('Select Received Date')
                                ->native(false)
                                ->dehydratedWhenHidden()
                                ->hidden(fn(Get $get): bool => !$get('is_received'))
                                ->columnSpan(fn(Get $get): ?string => $get('is_received') && (!$get('is_confirmed') && !$get('is_closed')) ? 'full' : null)
                                ->required(fn(Get $get): bool => $get('is_confirmed')),

                            DatePicker::make('closed_at')
                                ->native(false)
                                ->dehydratedWhenHidden()
                                ->hidden(fn(Get $get): bool => !$get('is_closed'))
                                ->columnSpan(fn(Get $get): ?string => $get('is_closed') && (!$get('is_confirmed') && !$get('is_received')) ? 'full' : null)
                                ->required(fn(Get $get): bool => $get('is_confirmed')),
                        ])
                            ->columns(3)
                            ->columnSpan(2)
                            ->hidden(fn(Get $get) => $get('is_confirmed') + $get('is_received') + $get('is_closed') === 2),

                        Group::make([
                            DatePicker::make('confirmed_at')
                                ->label('Confirmed At')
                                ->placeholder('Select Confirmed Date')
                                ->native(false)
                                ->dehydratedWhenHidden()
                                ->hidden(fn(Get $get): bool => !$get('is_confirmed'))
                                ->columnSpan(fn(Get $get): ?string => $get('is_confirmed') && (!$get('is_received') && !$get('is_closed')) ? 'full' : null)
                                ->required(fn(Get $get): bool => $get('is_confirmed')),

                            DatePicker::make('received_at')
                                ->label('Received At')
                                ->placeholder('Select Received Date')
                                ->native(false)
                                ->dehydratedWhenHidden()
                                ->hidden(fn(Get $get): bool => !$get('is_received'))
                                ->columnSpan(fn(Get $get): ?string => $get('is_received') && (!$get('is_confirmed') && !$get('is_closed')) ? 'full' : null)
                                ->required(fn(Get $get): bool => $get('is_confirmed')),

                            DatePicker::make('closed_at')
                                ->native(false)
                                ->dehydratedWhenHidden()
                                ->hidden(fn(Get $get): bool => !$get('is_closed'))
                                ->columnSpan(fn(Get $get): ?string => $get('is_closed') && (!$get('is_confirmed') && !$get('is_received')) ? 'full' : null)
                                ->required(fn(Get $get): bool => !$get('is_confirmed')),
                        ])
                            ->columns(2)
                            ->columnSpan(2)
                            ->hidden(fn(Get $get) => $get('is_confirmed') + $get('is_received') + $get('is_closed') !== 2),
                    ])->columns(2)
                    ->columnSpan(2),
            ])->columns(5);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('purchaseRequisition.number')
                    ->label('Purchase Requisition')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseRequisition.purchaseType.name')
                    ->label('Purchase Type')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('purchaseRequisition.requested_by')
                    ->label('Requestd By')
                    ->description(fn(PurchaseOrder $record): string => $record->PurchaseRequisition->Department->name),
                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('buyer')
                    ->label('Buyer')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_confirmed')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_received')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_closed')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('confirmed_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('received_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('closed_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
