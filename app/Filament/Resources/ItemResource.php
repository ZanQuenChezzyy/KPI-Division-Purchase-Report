<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\ItemReference;
use App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource\RelationManagers;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
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

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;
    protected static ?string $cluster = ItemReference::class;
    protected static ?string $label = 'Items';
    protected static ?string $navigationGroup = 'Items & Purchase Type';
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $activeNavigationIcon = 'heroicon-s-inbox-arrow-down';
    protected static ?int $navigationSort = 3;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() < 2 ? 'danger' : 'info';
    }
    protected static ?string $navigationBadgeTooltip = 'Total Items';
    protected static ?string $slug = 'items';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Item')
                    ->searchable(),
                TextColumn::make('unit_price')
                    ->label('Price')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('unit')
                    ->label('Unit')
                    ->searchable(),
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
                    ->tooltip('Action')
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageItems::route('/'),
        ];
    }
}
