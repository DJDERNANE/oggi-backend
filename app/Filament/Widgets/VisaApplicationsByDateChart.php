<?php

namespace App\Filament\Widgets;

use App\Models\VisaApplication;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VisaApplicationsByDateChart extends ChartWidget
{
    protected static ?string $heading = 'Daily Visa Applications';
    
    protected int | string | array $columnSpan = '1-2';

    // Add filter options
    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'week' => 'Last 7 days',
            'month' => 'Last 30 days',
            'quarter' => 'Last 90 days',
            'all' => 'All time',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter ?? 'week';
        
        $query = VisaApplication::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'));

        if ($filter !== 'all') {
            $startDate = match ($filter) {
                'today' => now()->startOfDay(),
                'week' => now()->subDays(7)->startOfDay(),
                'month' => now()->subDays(30)->startOfDay(),
                'quarter' => now()->subDays(90)->startOfDay(),
                default => now()->subDays(7)->startOfDay(),
            };
            $query->where('created_at', '>=', $startDate);
        }

        $data = $query->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Number of Applications',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => '#3b82f6', // Blue bars
                    'borderRadius' => 8,
                ],
            ],
            'labels' => $data->pluck('date')->map(function ($date) {
                return Carbon::parse($date)->format('D, M d'); // e.g., "Mon, Mar 15"
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getHeight(): int
    {
        return 300; // Height in pixels
    }
} 