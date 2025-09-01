<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Payment;
use App\Models\WalletTransaction;
use App\Models\LoyaltyPoints;
use App\Models\LoyaltyReward;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\ChartWidget;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;

class AnalyticsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?string $title = 'Analytics Dashboard';

    protected static ?string $slug = 'analytics';

    protected static ?int $navigationSort = 1;

    public function GetHeading(): string
    {
        return 'Business Analytics & Insights';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AnalyticsStatsOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            RevenueChart::class,
            UserGrowthChart::class,
            ServicePerformanceChart::class,
        ];
    }
}

class AnalyticsStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();
        $lastYear = $now->copy()->subYear();

        // Current month stats
        $currentMonthUsers = User::whereMonth('created_at', $now->month)->count();
        $currentMonthBookings = ServiceRequest::whereMonth('created_at', $now->month)->count();
        $currentMonthRevenue = Payment::where('status', 'paid')
            ->whereMonth('created_at', $now->month)
            ->sum('amount');

        // Last month stats for comparison
        $lastMonthUsers = User::whereMonth('created_at', $lastMonth->month)->count();
        $lastMonthBookings = ServiceRequest::whereMonth('created_at', $lastMonth->month)->count();
        $lastMonthRevenue = Payment::where('status', 'paid')
            ->whereMonth('created_at', $lastMonth->month)
            ->sum('amount');

        // Calculate growth percentages
        $userGrowth = $lastMonthUsers > 0 ? (($currentMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100 : 0;
        $bookingGrowth = $lastMonthBookings > 0 ? (($currentMonthBookings - $lastMonthBookings) / $lastMonthBookings) * 100 : 0;
        $revenueGrowth = $lastMonthRevenue > 0 ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        return [
            Stat::make('Total Users', User::count())
                ->description('All registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Monthly Users', $currentMonthUsers)
                ->description($userGrowth >= 0 ? "+{$userGrowth}%" : "{$userGrowth}%")
                ->descriptionIcon($userGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($userGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('Total Services', Service::count())
                ->description('Available services')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('info'),

            Stat::make('Monthly Bookings', $currentMonthBookings)
                ->description($bookingGrowth >= 0 ? "+{$bookingGrowth}%" : "{$bookingGrowth}%")
                ->descriptionIcon($bookingGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($bookingGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('Total Revenue', '$' . number_format(Payment::where('status', 'paid')->sum('amount'), 2))
                ->description('All time revenue')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Monthly Revenue', '$' . number_format($currentMonthRevenue, 2))
                ->description($revenueGrowth >= 0 ? "+{$revenueGrowth}%" : "{$revenueGrowth}%")
                ->descriptionIcon($revenueGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueGrowth >= 0 ? 'success' : 'danger'),

            Stat::make('Wallet Balance', '$' . number_format(User::sum('wallet_balance'), 2))
                ->description('Total user wallet funds')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),

            Stat::make('Loyalty Points', number_format(LoyaltyPoints::sum('points')))
                ->description('Total points in system')
                ->descriptionIcon('heroicon-m-gift')
                ->color('warning'),
        ];
    }
}

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue Trends (Last 12 Months)';

    protected function getData(): array
    {
        $months = collect();
        $revenue = collect();

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push($date->format('M Y'));
            
            $monthlyRevenue = Payment::where('status', 'paid')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount');
            
            $revenue->push(round($monthlyRevenue, 2));
        }

        return [
            'datasets' => [
                [
                    'label' => 'Monthly Revenue ($)',
                    'data' => $revenue->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

class UserGrowthChart extends ChartWidget
{
    protected static ?string $heading = 'User Growth (Last 12 Months)';

    protected function getData(): array
    {
        $months = collect();
        $users = collect();

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push($date->format('M Y'));
            
            $monthlyUsers = User::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            
            $users->push($monthlyUsers);
        }

        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $users->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

class ServicePerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'Service Performance';

    protected function getData(): array
    {
        $services = Service::withCount('serviceRequests')
            ->orderBy('service_requests_count', 'desc')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Bookings',
                    'data' => $services->pluck('service_requests_count')->toArray(),
                    'backgroundColor' => [
                        '#ef4444', '#f97316', '#eab308', '#84cc16', '#22c55e',
                        '#14b8a6', '#06b6d4', '#0ea5e9', '#6366f1', '#8b5cf6'
                    ],
                ],
            ],
            'labels' => $services->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
