<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers\PurchaseOrderLinesRelationManager;
use App\Models\PurchaseOrder;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Range;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group as GroupingGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
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
                            ->relationship(
                                'purchaseRequisition',
                                'id',
                                function (Builder $query, ?PurchaseOrder $record) {
                                    if ($record === null) {
                                        // Saat CREATE, tampilkan hanya PR yang belum ada di PO
                                        $search = request('search'); // Ambil nilai pencarian dari request
                                        $query->where('status', 2)
                                            ->whereNotIn('id', function ($subQuery) {
                                            $subQuery->select('purchase_requisition_id')->from('purchase_orders');
                                        })
                                            ->when(!empty($search), function ($query) use ($search) {
                                            $query->where(function ($q) use ($search) {
                                                $q->where('number', 'like', "%{$search}%")
                                                    ->orWhereHas('user', function ($q) use ($search) {
                                                        $q->where('name', 'like', "%{$search}%");
                                                    });
                                            });
                                        });
                                    } else {
                                        // Saat EDIT, hanya filter status = 2 agar data tetap tampil
                                        $query->where('status', 2);
                                    }
                                }
                            )
                            ->native(false)
                            ->preload()
                            ->columnSpanFull()
                            ->noSearchResultsMessage('No Purchase Requisition found.')
                            ->searchable(['number']) // Tetap menyertakan 'number' agar fitur searchable Filament bisa bekerja dengan baik
                            ->getOptionLabelFromRecordUsing(function (Model $record) {
                                $number = $record->number;
                                $type = $record->purchaseType->name;
                                $requestedBy = optional($record->user)->name ?? 'Unknown'; // Menghindari error jika user tidak ada
                                $department = $record->department->name;

                                return "($number) - $type [$requestedBy, $department]";
                            })
                            ->required(),

                        Select::make('vendor_id')
                            ->label('Vendor')
                            ->placeholder('Select Vendor')
                            ->relationship('vendor', 'name')
                            ->createOptionForm([
                                Group::make([
                                    TextInput::make('name')
                                        ->label('Vendors Name')
                                        ->placeholder('Enter Vendor Name')
                                        ->minLength(3)
                                        ->maxLength(45)
                                        ->required(),

                                    Select::make('type')
                                        ->label('Vendor Type')
                                        ->placeholder('Select Vendor Type')
                                        ->options([
                                            0 => 'International',
                                            1 => 'Domestic',
                                        ])
                                        ->native(false)
                                        ->preload()
                                        ->searchable()
                                        ->required(),
                                ])->columns(2)
                            ])
                            ->native(false)
                            ->preload()
                            ->searchable()
                            ->required(),

                        Select::make('buyer')
                            ->label('Buyer')
                            ->placeholder('Select Buyer Name')
                            ->relationship(
                                'user',
                                'name',
                                fn($query) => $query->whereHas('department', fn($q) => $q->where('name', 'Logistic')) // Filter berdasarkan nama department
                            )
                            ->native(false)
                            ->preload()
                            ->searchable()
                            ->required(),

                        DatePicker::make('eta')
                            ->label('ETA')
                            ->placeholder('Enter ETA')
                            ->native(false),

                        TextInput::make('mar_no')
                            ->label('Mar Number')
                            ->placeholder('Enter Mar Number')
                            ->minLength(3)
                            ->maxLength(15),
                    ])->columns(2)
                    ->columnSpan(3),
                Group::make([
                    Section::make()
                        ->schema([
                            Placeholder::make('created_by')
                                ->label('Created By')
                                ->content(fn(PurchaseOrder $record): ?string => $record->createdBy?->name),

                            Placeholder::make('updated_by')
                                ->label('Updated By')
                                ->content(fn(PurchaseOrder $record): ?string => $record->updatedBy?->name),
                        ])
                        ->columnSpan(2)
                        ->columns(2)
                        ->hidden(fn(?PurchaseOrder $record) => $record === null),
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
                                        })
                                        ->required(),

                                    TextInput::make('id')
                                        ->hidden(),

                                    Toggle::make('is_received')
                                        ->label('Order Received')
                                        ->inline(false)
                                        ->onColor('success')
                                        ->onIcon('heroicon-m-bolt')
                                        ->offIcon('heroicon-m-check')
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            if ($state) { // Cek jika user mencoba mengaktifkan toggle
                                                $purchaseOrderId = $get('id'); // Ambil ID Purchase Order

                                                // Cek apakah ada item yang belum berstatus '2' (Received)
                                                $hasUnreceivedItems = \App\Models\PurchaseOrderLine::where('purchase_order_id', $purchaseOrderId)
                                                    ->where('status', '!=', 2)
                                                    ->exists();

                                                if ($hasUnreceivedItems) {
                                                    // Hentikan perubahan dan tampilkan pesan error
                                                    $set('is_received', false); // Pastikan toggle tidak aktif
                                                    Notification::make()
                                                        ->title('Cannot mark as received')
                                                        ->body('Some items are not fully received yet.')
                                                        ->danger()
                                                        ->send();
                                                } else {
                                                    // Semua item sudah received, set tanggalnya
                                                    $set('received_at', now());
                                                }
                                            } else {
                                                // Jika toggle dimatikan, kosongkan tanggal
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
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            // Cek jika user mencoba mengaktifkan toggle
                                            if ($state) {
                                                $isReceived = $get('is_received'); // Ambil status is_received

                                                // Jika belum received, tolak aktivasi
                                                if (!$isReceived) {
                                                    $set('is_closed', false); // Kembalikan toggle ke off
                                                    Notification::make()
                                                        ->title('Cannot close order')
                                                        ->body('You must mark the order as received before closing it.')
                                                        ->danger()
                                                        ->send();
                                                    return false; // Hentikan update
                                                } else {
                                                    // Set tanggal closed jika received sudah true
                                                    $set('closed_at', now());
                                                }
                                            } else {
                                                // Jika toggle dimatikan, kosongkan closed_at
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
                                    ->required(fn(Get $get): bool => $get('is_closed')),
                            ])
                                ->columns(2)
                                ->columnSpan(2)
                                ->hidden(fn(Get $get) => $get('is_confirmed') + $get('is_received') + $get('is_closed') !== 2),
                        ])->columnSpan(2)
                        ->columns(2)
                ])->columnSpan(2)
            ])->columns(5);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->modifyQueryUsing(function ($query) {
                $query->orderByDesc('created_at');
            })
            ->columns([
                TextColumn::make('purchaseRequisition.number')
                    ->label('Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('purchaseRequisition.purchaseType.name')
                    ->label('Purchase Type')
                    ->badge()
                    ->color('info'),

                TextColumn::make('purchaseRequisition.user.name')
                    ->label('Requested By')
                    ->searchable()
                    ->description(fn(PurchaseOrder $record): string => $record->PurchaseRequisition->Department->name),

                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->default('No Vendor')
                    ->description(fn(PurchaseOrder $record): string => 
                        isset($record->vendor) 
                            ? match ($record->vendor->type) {
                                0 => 'International',
                                1 => 'Domestic',
                                default => 'Unknown',
                            }
                            : 'No Type' 
                    ),

                TextColumn::make('user.name')
                    ->label('Buyer')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('purchaseOrderLines.item.name')
                    ->label('Requisition Items')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->limit(20)
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->disabledClick(),

                TextColumn::make('purchaseOrderLines.total_price')
                    ->label('Total Price')
                    ->wrap()
                    ->listWithLineBreaks()
                    ->limit(20)
                    ->limitList(3)
                    ->numeric()
                    ->prefix('Rp ')
                    ->summarize([
                        Sum::make()
                            ->label('Expenses (IDR)')
                            ->prefix('Rp '),
                        Sum::make()
                            ->label('Expenses (USD)')
                            ->formatStateUsing(function ($state) {
                                $rate = \App\Services\ExchangeRateService::getRate('IDR', 'USD');
                                return $rate ? '$ ' . number_format($state * $rate, 2) : 'N/A';
                            }),
                    ])
                    ->expandableLimitedList()
                    ->disabledClick(),

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
                SelectFilter::make('purchaseRequisition.user.name')
                    ->label('Requested By')
                    ->placeholder('Select Requester')
                    ->relationship('purchaseRequisition.user', 'name')
                    ->native(false)
                    ->preload()
                    ->searchable(),

                SelectFilter::make('user.name')
                    ->label('Buyer')
                    ->placeholder('Select Buyer')
                    ->relationship('user', 'name')
                    ->native(false)
                    ->preload()
                    ->searchable(),

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

            ])->filtersFormColumns(3)
            ->filtersFormWidth(MaxWidth::FourExtraLarge)
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
            PurchaseOrderLinesRelationManager::class,
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
