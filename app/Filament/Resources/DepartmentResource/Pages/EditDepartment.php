<?php

namespace App\Filament\Resources\DepartmentResource\Pages;

use App\Filament\Resources\DepartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDepartment extends EditRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('View Department')
                ->icon('heroicon-s-eye'),
            Actions\DeleteAction::make()
                ->label('Delete Department')
                ->icon('heroicon-s-trash'),
        ];
    }
}
