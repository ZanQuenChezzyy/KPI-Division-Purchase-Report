<?php

namespace App\Filament\Resources;

use App\Filament\Exports\UserExporter;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $label = 'Users';
    protected static ?string $navigationGroup = 'Manage Users';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $activeNavigationIcon = 'heroicon-s-users';
    protected static ?int $navigationSort = 20;
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() < 2 ? 'danger' : 'info';
    }
    protected static ?string $navigationBadgeTooltip = 'Users Total';
    protected static ?string $slug = 'users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                FileUpload::make('avatar_url')
                                    ->label('Profile Picture')
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '1:1',
                                    ])
                                    ->imageCropAspectRatio('1:1')
                                    ->directory('avatar_upload')
                                    ->visibility('public')
                                    ->helperText('Supported formats: JPG, PNG, or GIF.')
                                    ->columnSpanFull(),
                            ]),
                        Section::make()
                            ->schema([
                                Select::make('roles')
                                    ->label('User Role')
                                    ->placeholder('Select User Role')
                                    ->relationship('roles', 'name')
                                    ->native(false)
                                    ->preload()
                                    ->searchable()
                                    ->columnSpanFull()
                                    ->required(),

                                Select::make('department')
                                    ->label('Department')
                                    ->placeholder('Select Department')
                                    ->relationship('department', 'name')
                                    ->native(false)
                                    ->preload()
                                    ->searchable()
                                    ->columnSpanFull()
                                    ->required(),
                            ]),
                    ])
                    ->columnSpan([
                        'default' => 3,
                        'sm' => 3,
                        'md' => 3,
                        'lg' => 4,
                        'xl' => 1,
                        '2xl' => 1,
                    ])
                    ->columns(1),

                Section::make('Users Information')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('filament-panels::pages/auth/edit-profile.form.name.label'))
                            ->placeholder(__('filament-panels::pages/auth/edit-profile.form.name.placeholder'))
                            ->inlineLabel()
                            ->columnSpanFull()
                            ->required()
                            ->minLength(3)
                            ->maxLength(45)
                            ->autofocus(),

                        TextInput::make('email')
                            ->label(__('filament-panels::pages/auth/edit-profile.form.email.label'))
                            ->placeholder(__('filament-panels::pages/auth/edit-profile.form.email.placeholder'))
                            ->inlineLabel()
                            ->columnSpanFull()
                            ->email()
                            ->required()
                            ->minLength(3)
                            ->maxLength(45)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label(function ($record) {
                                return $record ? __('Change Password') : __('Password');
                            })
                            ->placeholder(function ($record) {
                                return $record ? __('Optional: Only fill to update password') : __('Enter Password');
                            })
                            ->inlineLabel()
                            ->columnSpanFull()
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->rule(Password::default())
                            ->autocomplete('new-password')
                            ->dehydrated(fn($state): bool => filled($state))
                            ->dehydrateStateUsing(fn($state): string => Hash::make($state))
                            ->live(debounce: 500)
                            ->same('passwordConfirmation')
                            ->required(fn($record) => is_null($record)),

                        TextInput::make('passwordConfirmation')
                            ->label(__('Password Confirmation'))
                            ->placeholder(__('Enter Password Confirmation'))
                            ->inlineLabel()
                            ->columnSpanFull()
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->required()
                            ->visible(fn(Get $get): bool => filled($get('password')))
                            ->dehydrated(false),
                    ])->columnSpan([
                            'default' => fn(?User $record) => $record === null ? 3 : 3,
                            'sm' => fn(?User $record) => $record === null ? 2 : 3,
                            'md' => fn(?User $record) => $record === null ? 3 : 3,
                            'lg' => fn(?User $record) => $record === null ? 4 : 4,
                            'xl' => fn(?User $record) => $record === null ? 3 : 2,
                            '2xl' => fn(?User $record) => $record === null ? 3 : 2,
                        ])
                    ->columns(2),

                Section::make()
                    ->schema([
                        Placeholder::make('created_at')
                            ->label('Created At')
                            ->content(fn(User $record): ?string => $record->created_at?->diffForHumans()),

                        Placeholder::make('updated_at')
                            ->label('Updated At')
                            ->content(fn(User $record): ?string => $record->created_at?->diffForHumans()),
                    ])
                    ->columnSpan([
                        'default' => 3,
                        'sm' => 3,
                        'md' => 3,
                        'lg' => 4,
                        'xl' => 1,
                        '2xl' => 1,
                    ])
                    ->hidden(fn(?User $record) => $record === null)
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Users')
                    ->formatStateUsing(function (User $record) {
                        $nameParts = explode(' ', trim($record->name));
                        $initials = isset($nameParts[1])
                            ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
                            : strtoupper(substr($nameParts[0], 0, 1));
                        $avatarUrl = $record->avatar_url
                            ? asset('storage/' . $record->avatar_url)
                            : 'https://ui-avatars.com/api/?name=' . $initials . '&amp;color=FFFFFF&amp;background=030712';
                        $image = '<img class="w-10 h-10 rounded-lg" style="margin-right: 0.625rem !important;" src="' . $avatarUrl . '" alt="Avatar User">';
                        $name = '<strong class="text-sm font-medium text-gray-800">' . e($record->name) . '</strong>';
                        $email = '<span class="font-light text-gray-300">' . e($record->email) . '</span>';
                        return '<div class="flex items-center" style="margin-right: 4rem !important">'
                            . $image
                            . '<div>' . $name . '<br>' . $email . '</div></div>';
                    })
                    ->html()
                    ->searchable(['name', 'email']),

                TextColumn::make('department.name')
                    ->label('Department')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->colors([
                        'info',
                    ])
                    ->badge()
                    ->separator(', ')
                    ->limitList(3)
                    ->wrap(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->color('info'),
                    DeleteAction::make()
                        ->authorize(function ($record) {
                            return Auth::id() !== $record->id;
                        })
                        ->using(function ($record) {
                            if ($record->id === Auth::id()) {
                                session()->flash('error', 'You cannot delete your own account.');
                                return false;
                            }
                            $record->delete();
                        })
                        ->requiresConfirmation(),
                ])
                    ->icon('heroicon-o-ellipsis-horizontal-circle')
                    ->color('info')
                    ->tooltip('Action')
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(function ($records) {
                            $recordsToDelete = $records->reject(function ($record) {
                                return $record->id === Auth::id();
                            });
                            $recordsToDelete->each(function ($record) {
                                $record->delete();
                            });
                            session()->flash('message', 'Selected accounts were deleted, except for your own account.');
                        })
                        ->requiresConfirmation(),
                    // ExportBulkAction::make()
                    //     ->exporter(UserExporter::class)
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
