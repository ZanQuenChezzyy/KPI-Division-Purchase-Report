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
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Range;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group as GroupingGroup;
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
                                    ->onColor('success')
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
                                ->required(fn(Get $get): bool => $get('is_received')),

                            DatePicker::make('closed_at')
                                ->label('Closed At')
                                ->placeholder('Select Closed Date')
                                ->native(false)
                                ->dehydratedWhenHidden()
                                ->hidden(fn(Get $get): bool => !$get('is_closed'))
                                ->columnSpan(fn(Get $get): ?string => $get('is_closed') && (!$get('is_confirmed') && !$get('is_received')) ? 'full' : null)
                                ->required(fn(Get $get): bool => $get('is_closed')),
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
                                ->required(fn(Get $get): bool => $get('is_received')),

                            DatePicker::make('closed_at')
                                ->label('Closed At')
                                ->placeholder('Select Closed Date')
                                ->native(false)
                                ->dehydratedWhenHidden()
                                ->hidden(fn(Get $get): bool => !$get('is_closed'))
                                ->columnSpan(fn(Get $get): ?string => $get('is_closed') && (!$get('is_confirmed') && !$get('is_received')) ? 'full' : null)
                                ->required(fn(Get $get): bool => !$get('is_closed')),
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
            ->Groups([
                GroupingGroup::make('purchaseRequisition.department.name')
                    ->label('Department Name'),
                GroupingGroup::make('vendor.name')
                    ->label('Vendor Name'),
            ])
            ->defaultGroup('purchaseRequisition.department.name')
            ->columns([
                TextColumn::make('purchaseRequisition.number')
                    ->label('Number')
                    ->sortable(),

                TextColumn::make('purchaseRequisition.purchaseType.name')
                    ->label('Purchase Type')
                    ->badge()
                    ->color('info'),

                TextColumn::make('purchaseRequisition.requested_by')
                    ->label('Requested By')
                    ->description(fn(PurchaseOrder $record): string => $record->PurchaseRequisition->Department->name),

                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->description(fn(PurchaseOrder $record): string => $record->vendor->type),

                TextColumn::make('buyer')
                    ->label('Buyer')
                    ->searchable(),

                TextColumn::make('purchaseRequisition.status')
                    ->label('Requisition Status')
                    ->badge()
                    ->icon(fn(int $state): string => match ($state) {
                        0 => 'heroicon-o-clock',
                        1 => 'heroicon-o-x-circle',
                        2 => 'heroicon-o-check-circle',
                    })
                    ->formatStateUsing(fn(int $state): string => match ($state) {
                        0 => 'Pending',
                        1 => 'Cancelled',
                        2 => 'Approved',
                        default => 'Status Tidak Diketahui',
                    })
                    ->color(fn(int $state): string => match ($state) {
                        0 => 'warning',
                        1 => 'danger',
                        2 => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('purchaseRequisition.purchaseRequisitionItems.item.name')
                    ->label('Requisition Items')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->limit(20)
                    ->limitList(3)
                    ->expandableLimitedList(),

                TextColumn::make('purchaseRequisition.purchaseRequisitionItems.total_price')
                    ->label('Price')
                    ->wrap()
                    ->listWithLineBreaks()
                    ->limit(20)
                    ->limitList(3)
                    ->numeric()
                    ->prefix('Rp ')
                    ->summarize([
                        Average::make()
                            ->label('')
                            ->prefix('Rp '),
                    ]),

                ToggleColumn::make('is_confirmed')
                    ->label('Is Confirmed')
                    ->onColor('success')
                    ->onIcon('heroicon-m-bolt')
                    ->offIcon('heroicon-m-check')
                    ->afterStateUpdated(function ($record, $state) {
                        if ($state) {
                            $record->update(['confirmed_at' => now()]);
                        } else {
                            $record->update(['confirmed_at' => null]);
                        }
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('confirmed_at')
                    ->placeholder('No Data Recorded.')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_received')
                    ->label('Is Received')
                    ->onColor('success')
                    ->onIcon('heroicon-m-bolt')
                    ->offIcon('heroicon-m-check')
                    ->afterStateUpdated(function ($record, $state) {
                        if ($state) {
                            $record->update(['received_at' => now()]);
                        } else {
                            $record->update(['received_at' => null]);
                        }
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('received_at')
                    ->placeholder('No Data Recorded.')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_closed')
                    ->label('Is Closed')
                    ->onColor('success')
                    ->onIcon('heroicon-m-bolt')
                    ->offIcon('heroicon-m-check')
                    ->afterStateUpdated(function ($record, $state) {
                        if ($state) {
                            $record->update(['closed_at' => now()]);
                        } else {
                            $record->update(['closed_at' => null]);
                        }
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('closed_at')
                    ->placeholder('No Data Recorded.')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                SelectFilter::make('purchaseRequisition.purchaseType.name')
                    ->label('Purchase Type')
                    ->placeholder('Select Purchase Type')
                    ->relationship('purchaseRequisition.purchaseType', 'name')
                    ->native(false)
                    ->preload()
                    ->searchable(),

                SelectFilter::make('purchaseRequisition.Department.name')
                    ->label('Department')
                    ->placeholder('Select Deparment')
                    ->relationship('purchaseRequisition.Department', 'name')
                    ->native(false)
                    ->preload()
                    ->searchable(),

                TernaryFilter::make('is_confirmed')
                    ->placeholder('Select Options')
                    ->native(false)
                    ->preload()
                    ->searchable(),

                TernaryFilter::make('is_received')
                    ->placeholder('Select Options')
                    ->native(false)
                    ->preload()
                    ->searchable(),

                TernaryFilter::make('is_closed')
                    ->placeholder('Select Options')
                    ->native(false)
                    ->preload()
                    ->searchable(),

            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->color('info'),
                    DeleteAction::make(),
                ])
                    ->icon('heroicon-o-ellipsis-horizontal-circle')
                    ->color('info')
                    ->tooltip('Action'),
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
