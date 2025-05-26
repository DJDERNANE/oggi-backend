<?php

namespace App\Filament\Widgets;

use App\Models\VisaApplication;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class VisaApplicationsChart extends ChartWidget
{
    protected static ?string $heading = 'Visa Applications';

    protected function getData(): array
    {
        $data = VisaApplication::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Visa Applications by Status',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => [
                        '#f59e0b', // warning - pending
                        '#10b981', // success - approved
                        '#ef4444', // danger - rejected
                        '#3b82f6', // info - action required
                    ],
                ],
            ],
            'labels' => $data->pluck('status')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
} 