<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseRequisitionResource\Pages;
use App\Filament\Resources\PurchaseRequisitionResource\RelationManagers;
use App\Models\PurchaseRequisition;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group as GroupingGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

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
                                TextInput::make('number')
                                    ->label('Purchase Requisition Number')
                                    ->placeholder('Enter Number')
                                    ->minValue(1)
                                    ->minLength(3)
                                    ->maxLength(10)
                                    ->numeric()
                                    ->required(),
                                Select::make('purchase_type_id')
                                    ->label('Purchase Type')
                                    ->placeholder('Select Purchase Type')
                                    ->relationship('purchaseType', 'name')
                                    ->native(false)
                                    ->preload()
                                    ->searchable()
                                    ->required(),
                                Textarea::make('description')
                                    ->label('Description')
                                    ->placeholder('Enter Description')
                                    ->minLength(10)
                                    ->rows(3)
                                    ->autosize()
                                    ->required()
                                    ->columnSpanFull(),
                            ])->columns(2)
                            ->columnSpan(1),
                        Section::make('Requester Details')
                            ->schema([
                                Select::make('requested_by')
                                    ->label('Requester By')
                                    ->placeholder('Select Requester')
                                    ->relationship('UserDepartment', 'id', function ($query) {
                                        return $query->with('User');
                                    })
                                    ->getOptionLabelFromRecordUsing(fn($record) => $record->User->name)
                                    ->native(false)
                                    ->preload()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        // Ambil department_id dari user_department yang dipilih
                                        $departmentId = \App\Models\UserDepartment::where('id', $state)->value('department_id');

                                        // Set department_id secara otomatis
                                        $set('department_id', $departmentId);
                                    })
                                    ->required(),
                                Select::make('department_id')
                                    ->label('Department')
                                    ->placeholder('Select Department')
                                    ->relationship('department', 'name')
                                    ->native(false)
                                    ->preload()
                                    ->hint('Automatic')
                                    ->searchable()
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                            ])->columns(2)
                            ->columnSpan(1),
                    ]),
                Group::make()
                    ->schema([
                        Section::make('Status & Approval')
                            ->schema([
                                Select::make('status')
                                    ->label('Purchase Requisition Status')
                                    ->placeholder('Select Purchase Requisition Status')
                                    ->options([
                                        0 => 'Pending',
                                        1 => 'Cancelled',
                                        2 => 'Approved',
                                    ])
                                    ->native(false)
                                    ->preload()
                                    ->searchable()
                                    ->reactive()
                                    ->columnSpanFull()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state === '2') {
                                            $set('approved_at', now());
                                            $set('cancelled_at', null);
                                        } elseif ($state === '1') {
                                            $set('cancelled_at', now());
                                            $set('approved_at', null);
                                        } else {
                                            $set('approved_at', null);
                                            $set('cancelled_at', null);
                                        }
                                    })
                                    ->columnSpan(fn(Get $get) => in_array($get('status'), ['0', null]) ? 2 : 1)
                                    ->default(0)
                                    ->required(),

                                DatePicker::make('approved_at')
                                    ->label('Approved At')
                                    ->placeholder('Select Approved Date')
                                    ->native(false)
                                    ->dehydratedWhenHidden()
                                    ->hidden(fn(Get $get): bool => $get('status') !== '2' && !$get('approved_at') || $get('status') === '0' || $get('status') === null),

                                DatePicker::make('cancelled_at')
                                    ->label('Cancelled At')
                                    ->placeholder('Select Cancelled Date')
                                    ->native(false)
                                    ->dehydratedWhenHidden()
                                    ->hidden(fn(Get $get): bool => $get('status') !== '1' && !$get('cancelled_at') || $get('status') === '0' || $get('status') === null),
                            ])->columns(2)
                            ->columnSpan(1),
                        Section::make('Purchase Items')
                            ->schema([
                                Repeater::make('Items')
                                    ->label('Purchase Requisition Line')
                                    ->relationship('purchaseRequisitionItems')
                                    ->schema([
                                        Select::make('item_id')
                                            ->label('Item')
                                            ->placeholder('Select Item')
                                            ->relationship('Item', 'name')
                                            ->createOptionForm([
                                                TextInput::make('name')
                                                    ->label('Item Name')
                                                    ->placeholder('Enter Item Name')
                                                    ->minLength(3)
                                                    ->maxLength(100)
                                                    ->columnSpanFull()
                                                    ->required(),

                                                Group::make()
                                                    ->schema([
                                                        TextInput::make('sku')
                                                            ->label('Stock Keeping Unit (SKU)')
                                                            ->placeholder('Enter Stock Keeping Unit')
                                                            ->minLength(3)
                                                            ->maxLength(50)
                                                            ->columnSpan(2)
                                                            ->required(),

                                                        TextInput::make('unit_price')
                                                            ->label('Price / unit')
                                                            ->placeholder('Enter Price')
                                                            ->minValue(1000)
                                                            ->minLength(4)
                                                            ->maxLength(20)
                                                            ->columnSpan(2)
                                                            ->mask(RawJs::make('$money($input)'))
                                                            ->stripCharacters(',')
                                                            ->prefix('Rp')
                                                            ->suffix('.00')
                                                            ->numeric()
                                                            ->required(),

                                                        TextInput::make('unit')
                                                            ->label('Unit')
                                                            ->placeholder('Enter Unit')
                                                            ->helperText('E.g., Pcs, Kg, Liter')
                                                            ->minLength(1)
                                                            ->maxLength(20)
                                                            ->required(),
                                                    ])->columns(5)
                                                    ->columnSpanFull(),

                                                Textarea::make('description')
                                                    ->label('Description')
                                                    ->placeholder('Enter Description')
                                                    ->minLength(10)
                                                    ->rows(3)
                                                    ->autosize()
                                                    ->columnSpanFull(),
                                            ])
                                            ->native(false)
                                            ->preload()
                                            ->searchable()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                $item = \App\Models\Item::find($state);
                                                $unitPrice = (float) ($item?->unit_price ?? 0);
                                                $set('unit_price', $unitPrice);

                                                $qty = (int) ($get('qty') ?? 1);
                                                $totalPrice = $unitPrice * $qty;

                                                $formattedTotalPrice = number_format($totalPrice, 0, '.', ',');
                                                $set('total_price', $formattedTotalPrice);
                                            })
                                            ->columnSpan(3)
                                            ->required(),
                                        TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->hidden()
                                            ->dehydratedWhenHidden()
                                            ->numeric(),
                                        TextInput::make('qty')
                                            ->label('Quantity')
                                            ->placeholder('Qty')
                                            ->minValue(1)
                                            ->maxValue(999)
                                            ->minLength(1)
                                            ->maxLength(3)
                                            ->numeric()
                                            ->live(debounce: 800)
                                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                $unitPrice = $get('unit_price') ?? 0;
                                                $totalPrice = $unitPrice * $state;
                                                $formattedTotalPrice = number_format($totalPrice, 0, '.', ',');
                                                $set('total_price', $formattedTotalPrice);
                                            })
                                            ->columnSpan(1)
                                            ->required(),
                                        TextInput::make('total_price')
                                            ->label('Estimated Total Price')
                                            ->placeholder('Price')
                                            ->mask(RawJs::make('$money($input)'))
                                            ->stripCharacters(',')
                                            ->columnSpan(2)
                                            ->disabled()
                                            ->numeric()
                                            ->dehydrated(),
                                    ])->addActionLabel('Add Another Items')
                                    ->reorderable(true)
                                    ->reorderableWithButtons()
                                    ->columns(6),
                            ])
                    ]),
            ])->columns(2);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Purchase Requisition')
                    ->schema([
                        Fieldset::make('Status & Approval')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status')
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
                                TextEntry::make('approved_at')
                                    ->label('Approved At')
                                    ->formatStateUsing(fn($state): string => $state ? Carbon::parse($state)->translatedFormat('l, d F Y') : '-')
                                    ->visible(fn($record): bool => $record->status === 2),

                                TextEntry::make('cancelled_at')
                                    ->label('Cancelled At')
                                    ->formatStateUsing(fn($state): string => $state ? Carbon::parse($state)->translatedFormat('l, d F Y') : '-')
                                    ->visible(fn($record): bool => $record->status === 1),
                            ])->columns(2)
                            ->columnSpan(5),
                        Fieldset::make('Timestamps')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime(),
                                TextEntry::make('updated_at')
                                    ->label('Updated At')
                                    ->dateTime(),
                            ])->columns(2)
                            ->columnSpan(3),
                        Fieldset::make('General Information')
                            ->schema([
                                TextEntry::make('number')
                                    ->label('Number'),
                                TextEntry::make('purchaseType.name')
                                    ->label('Purchase Type')
                                    ->badge()
                                    ->color('info'),
                            ])->columns(2)
                            ->columnSpan(4),
                        Fieldset::make('Requester Details')
                            ->schema([
                                TextEntry::make('UserDepartment.User.name')
                                    ->label('Requested By'),
                                TextEntry::make('department.name')
                                    ->label('Department')
                            ])->columns(2)
                            ->columnSpan(4),
                        Fieldset::make('Description')
                            ->schema([
                                TextEntry::make('description')
                                    ->label('')
                            ])->columns(1)
                            ->columnSpan(4),
                        RepeatableEntry::make('purchaseRequisitionItems')
                            ->label('Purchase Requisition Line')
                            ->schema([
                                TextEntry::make('Item.name')
                                    ->label('Item Name')
                                    ->columnSpan(3),
                                TextEntry::make('qty')
                                    ->prefix('x ')
                                    ->columnSpan(1),
                                TextEntry::make('total_price')
                                    ->label('Estimated Total Price')
                                    ->prefix('Rp ')
                                    ->suffix('.00')
                                    ->numeric()
                                    ->columnSpan(2)
                            ])->columns(6)
                            ->columnSpan(4)
                    ])->columns(8)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->groups([
                GroupingGroup::make('Department.name')
                    ->label('Department'),
                GroupingGroup::make('status')
                    ->getTitleFromRecordUsing(fn(PurchaseRequisition $record): string => match ($record->status) {
                        1 => 'Pending',
                        2 => 'Approved',
                        3 => 'Cancelled',
                        default => 'Unknown',
                    }),
            ])
            ->defaultGroup('Department.name')
            ->columns([
                TextColumn::make('number')
                    ->label('Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('purchaseType.name')
                    ->label('Purchase Type')
                    ->badge()
                    ->color('info'),

                TextColumn::make('UserDepartment.User.name')
                    ->label('Requested By')
                    ->description(fn(PurchaseRequisition $record): string => $record->Department->name)
                    ->searchable(['department_id', 'requested_by']),

                TextColumn::make('status')
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
                        default => 'gray',  // Jika status tidak diketahui
                    }),

                TextColumn::make('approved_at')
                    ->date()
                    ->placeholder('No Approval Recorded.')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cancelled_at')
                    ->date()
                    ->placeholder('No Cancellation Recorded.')
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
                SelectFilter::make('status')
                    ->label('Status')
                    ->placeholder('Select Status')
                    ->options([
                        0 => 'Pending',
                        1 => 'Cancelled',
                        2 => 'Approved',
                    ])
                    ->native(false)
                    ->preload()
                    ->searchable(),
                SelectFilter::make('purchase_type_id')
                    ->label('Purchase Type')
                    ->placeholder('Select Purchase Type')
                    ->relationship('purchaseType', 'name')
                    ->native(false)
                    ->preload()
                    ->searchable(),
                SelectFilter::make('user_id')
                    ->label('Requester')
                    ->placeholder('Select Requester')
                    ->relationship('UserDepartment.User', 'name')
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
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
