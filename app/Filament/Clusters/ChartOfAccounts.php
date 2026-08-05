<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ChartOfAccounts extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static ?string $navigationLabel = 'Chart of Accounts';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::End;

    protected static string|UnitEnum|null $navigationGroup = 'Budgeting';
}
