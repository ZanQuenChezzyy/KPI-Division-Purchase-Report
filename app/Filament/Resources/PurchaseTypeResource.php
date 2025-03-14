<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\ItemReference;
use App\Filament\Resources\PurchaseTypeResource\Pages;
use App\Filament\Resources\PurchaseTypeResource\RelationManagers;
use App\Models\PurchaseType;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
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

class PurchaseTypeResource extends Resource
{
    protected static ?string $model = PurchaseType::class;
    protected static ?string $cluster = ItemReference::class;
    protected static ?string $label = 'Purchase Type';
    protected static ?string $navigationGroup = 'Items & Purchase Type';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $activeNavigationIcon = 'heroicon-s-banknotes';
    protected static ?int $navigationSort = 4;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() < 2 ? 'danger' : 'info';
    }
    protected static ?string $navigationBadgeTooltip = 'Total Purchase Type';
    protected static ?string $slug = 'purchase-type';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('id')
                    ->label('Purchase Type ID')
                    ->placeholder('Enter Purchase Type ID')
                    ->numeric()
                    ->required(),
                TextInput::make('name')
                    ->label('Purchase Type Name')
                    ->placeholder('Enter Purchase Type Name')
                    ->minLength(3)
                    ->maxLength(45)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Purchase Type')
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
            'index' => Pages\ManagePurchaseTypes::route('/'),
        ];
    }
}
