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
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
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
                                TextInput::make('requested_by')
                                    ->label('Requester By')
                                    ->placeholder('Enter Requester Full Name')
                                    ->minLength(3)
                                    ->maxLength(45)
                                    ->required(),
                                Select::make('department_id')
                                    ->label('Department')
                                    ->placeholder('Select Department')
                                    ->relationship('department', 'name')
                                    ->native(false)
                                    ->preload()
                                    ->searchable()
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
                                        if ($state === '1' || $state === '2') {
                                            $set('approved_at', now());
                                            $set('cancelled_at', now());
                                        }
                                    })
                                    ->columnSpan(fn(Get $get) => in_array($get('status'), ['0', null]) ? 2 : 1)
                                    ->default(0)
                                    ->required(),

                                DatePicker::make('approved_at')
                                    ->label('Approved At')
                                    ->placeholder('Select Approved Date')
                                    ->native(false)
                                    ->visible(fn(Get $get): bool => $get('status') === '2')
                                    ->required(fn(Get $get): bool => $get('status') === '2'),

                                DatePicker::make('cancelled_at')
                                    ->label('Cancelled At')
                                    ->placeholder('Select Cancelled Date')
                                    ->native(false)
                                    ->visible(fn(Get $get): bool => $get('status') === '1')
                                    ->required(fn(Get $get): bool => $get('status') === '1'),
                            ])->columns(2)
                            ->columnSpan(1),
                        Section::make('Purchase Items')
                            ->schema([
                                Repeater::make('Items')
                                    ->label('Purchase Requisition Items')
                                    ->relationship('purchaseRequisitionItems')
                                    ->schema([
                                        Select::make('item_id')
                                            ->label('Item')
                                            ->placeholder('Select Item')
                                            ->relationship('Item', 'name')
                                            ->native(false)
                                            ->preload()
                                            ->searchable()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                $unitPrice = \App\Models\Item::find($state)?->unit_price ?? 0;
                                                $set('unit_price', $unitPrice);
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
                                            ->label('Price')
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Number')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('purchaseType.name')
                    ->label('Purchase Type')
                    ->sortable(),

                TextColumn::make('requested_by')
                    ->description(fn(PurchaseRequisition $record): string => $record->Department->name)
                    ->searchable(['department.name', 'requested_by']),

                TextColumn::make('status')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('approved_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cancelled_at')
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
                //
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->color('primary'),
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
