<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Payment;
use App\Models\WalletTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalUsers = User::count();
        $totalServices = Service::count();
        $totalBookings = ServiceRequest::count();
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $totalWalletBalance = User::sum('wallet_balance');
        $pendingBookings = ServiceRequest::where('status', 'pending')->count();
        $completedBookings = ServiceRequest::where('status', 'completed')->count();
        $activeUsers = User::where('created_at', '>=', now()->subDays(30))->count();

        return [
            Stat::make('Total Users', $totalUsers)
                ->description('Registered customers')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Total Services', $totalServices)
                ->description('Available services')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('info'),

            Stat::make('Total Bookings', $totalBookings)
                ->description('Service requests')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),

            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description('From completed payments')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Wallet Balance', '$' . number_format($totalWalletBalance, 2))
                ->description('Total user wallet funds')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),

            Stat::make('Pending Bookings', $pendingBookings)
                ->description('Awaiting confirmation')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Completed Bookings', $completedBookings)
                ->description('Successfully delivered')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Active Users (30d)', $activeUsers)
                ->description('New registrations')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info'),
        ];
    }
}
