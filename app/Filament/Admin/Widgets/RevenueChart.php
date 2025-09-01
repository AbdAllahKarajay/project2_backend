<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue Trends';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $days = collect();
        $revenue = collect();

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $days->push($date->format('M d'));
            
            $dailyRevenue = Payment::where('status', 'paid')
                ->whereDate('created_at', $date)
                ->sum('amount');
            
            $revenue->push(round($dailyRevenue, 2));
        }

        return [
            'datasets' => [
                [
                    'label' => 'Daily Revenue ($)',
                    'data' => $revenue->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $days->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
