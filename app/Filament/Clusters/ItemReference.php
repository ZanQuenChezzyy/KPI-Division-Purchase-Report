<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class ItemReference extends Cluster
{
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $title = 'Item & Reference';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';
    protected static ?string $activeNavigationIcon = 'heroicon-s-clipboard-document';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'item-reference';
}
