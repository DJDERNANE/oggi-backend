<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\VisaApplication;
use App\Models\User;
use App\Models\Destination;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::query()->count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
                
            Stat::make('Total Destinations', Destination::query()->count())
                ->description('Available countries')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('warning')
                ->chart([3, 5, 7, 6, 3, 5, 3, 4]),
                
            Stat::make('Total Applications', VisaApplication::query()->count())
                ->description('Visa applications')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->chart([4, 3, 5, 3, 7, 4, 5, 3]),
        ];
    }
}
