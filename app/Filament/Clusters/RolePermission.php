<?php

namespace App\Filament\Clusters;

use App\Models\Role;
use Filament\Clusters\Cluster;

class RolePermission extends Cluster
{
    protected static ?string $model = Role::class;
    protected static ?string $navigationGroup = 'Manage Users';
    protected static ?string $title = 'Access & Permission';
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $activeNavigationIcon = 'heroicon-s-shield-check';
    protected static ?int $navigationSort = 19;
    protected static ?string $slug = 'access-Permission';
}
