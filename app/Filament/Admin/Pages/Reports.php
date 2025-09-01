<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Payment;
use App\Models\WalletTransaction;
use App\Models\LoyaltyPoints;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Reports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $title = 'Reports & Analytics';

    protected static ?string $slug = 'reports';

    protected static ?int $navigationSort = 4;

    public function getHeading(): string
    {
        return 'Business Reports & Data Export';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_users')
                ->label('Export Users')
                ->icon('heroicon-m-users')
                ->color('success')
                ->form([
                    DatePicker::make('date_from')
                        ->label('From Date')
                        ->default(now()->subMonth()),
                    DatePicker::make('date_to')
                        ->label('To Date')
                        ->default(now()),
                    Select::make('role')
                        ->label('Role')
                        ->options([
                            'all' => 'All Roles',
                            'admin' => 'Admin',
                            'customer' => 'Customer',
                        ])
                        ->default('all'),
                ])
                ->action(function (array $data): StreamedResponse {
                    return $this->exportUsers($data);
                }),

            Action::make('export_bookings')
                ->label('Export Bookings')
                ->icon('heroicon-m-calendar')
                ->color('info')
                ->form([
                    DatePicker::make('date_from')
                        ->label('From Date')
                        ->default(now()->subMonth()),
                    DatePicker::make('date_to')
                        ->label('To Date')
                        ->default(now()),
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'all' => 'All Statuses',
                            'pending' => 'Pending',
                            'assigned' => 'Assigned',
                            'in_progress' => 'In Progress',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('all'),
                ])
                ->action(function (array $data): StreamedResponse {
                    return $this->exportBookings($data);
                }),

            Action::make('export_payments')
                ->label('Export Payments')
                ->icon('heroicon-m-currency-dollar')
                ->color('warning')
                ->form([
                    DatePicker::make('date_from')
                        ->label('From Date')
                        ->default(now()->subMonth()),
                    DatePicker::make('date_to')
                        ->label('To Date')
                        ->default(now()),
                    Select::make('method')
                        ->label('Payment Method')
                        ->options([
                            'all' => 'All Methods',
                            'cash' => 'Cash',
                            'wallet' => 'Wallet',
                            'third_party' => 'Third Party',
                        ])
                        ->default('all'),
                ])
                ->action(function (array $data): StreamedResponse {
                    return $this->exportPayments($data);
                }),

            Action::make('export_wallet_transactions')
                ->label('Export Wallet Transactions')
                ->icon('heroicon-m-wallet')
                ->color('primary')
                ->form([
                    DatePicker::make('date_from')
                        ->label('From Date')
                        ->default(now()->subMonth()),
                    DatePicker::make('date_to')
                        ->label('To Date')
                        ->default(now()),
                    Select::make('type')
                        ->label('Transaction Type')
                        ->options([
                            'all' => 'All Types',
                            'topup' => 'Top Up',
                            'payment' => 'Payment',
                            'refund' => 'Refund',
                            'bonus' => 'Bonus',
                            'deduction' => 'Deduction',
                        ])
                        ->default('all'),
                ])
                ->action(function (array $data): StreamedResponse {
                    return $this->exportWalletTransactions($data);
                }),

            Action::make('export_loyalty_points')
                ->label('Export Loyalty Points')
                ->icon('heroicon-m-gift')
                ->color('success')
                ->form([
                    DatePicker::make('date_from')
                        ->label('From Date')
                        ->default(now()->subMonth()),
                    DatePicker::make('date_to')
                        ->label('To Date')
                        ->default(now()),
                ])
                ->action(function (array $data): StreamedResponse {
                    return $this->exportLoyaltyPoints($data);
                }),
        ];
    }

    protected function getViewData(): array
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();

        return [
            'monthlyStats' => $this->getMonthlyStats($lastMonth),
            'topServices' => $this->getTopServices(),
            'topUsers' => $this->getTopUsers(),
            'revenueTrends' => $this->getRevenueTrends(),
        ];
    }

    private function exportUsers(array $data): StreamedResponse
    {
        $query = User::query();

        if ($data['date_from']) {
            $query->where('created_at', '>=', $data['date_from']);
        }

        if ($data['date_to']) {
            $query->where('created_at', '<=', $data['date_to']);
        }

        if ($data['role'] !== 'all') {
            $query->where('role', $data['role']);
        }

        $users = $query->get();

        $filename = 'users_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->stream(function () use ($users) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'Role', 'Wallet Balance', 'Created At']);
            
            // Data
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone,
                    $user->role,
                    $user->wallet_balance,
                    $user->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function exportBookings(array $data): StreamedResponse
    {
        $query = ServiceRequest::with(['user', 'service', 'location']);

        if ($data['date_from']) {
            $query->where('created_at', '>=', $data['date_from']);
        }

        if ($data['date_to']) {
            $query->where('created_at', '<=', $data['date_to']);
        }

        if ($data['status'] !== 'all') {
            $query->where('status', $data['status']);
        }

        $bookings = $query->get();

        $filename = 'bookings_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->stream(function () use ($bookings) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, ['ID', 'Customer', 'Service', 'Status', 'Total Price', 'Scheduled At', 'Created At']);
            
            // Data
            foreach ($bookings as $booking) {
                fputcsv($file, [
                    $booking->id,
                    $booking->user->name,
                    $booking->service->name,
                    $booking->status,
                    $booking->total_price,
                    $booking->scheduled_at ? $booking->scheduled_at->format('Y-m-d H:i:s') : '',
                    $booking->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function exportPayments(array $data): StreamedResponse
    {
        $query = Payment::with(['serviceRequest.user', 'serviceRequest.service']);

        if ($data['date_from']) {
            $query->where('created_at', '>=', $data['date_from']);
        }

        if ($data['date_to']) {
            $query->where('created_at', '<=', $data['date_to']);
        }

        if ($data['method'] !== 'all') {
            $query->where('method', $data['method']);
        }

        $payments = $query->get();

        $filename = 'payments_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->stream(function () use ($payments) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, ['ID', 'Invoice Number', 'Customer', 'Service', 'Amount', 'Method', 'Status', 'Created At']);
            
            // Data
            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->id,
                    $payment->invoice_number,
                    $payment->serviceRequest->user->name,
                    $payment->serviceRequest->service->name,
                    $payment->amount,
                    $payment->method,
                    $payment->status,
                    $payment->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function exportWalletTransactions(array $data): StreamedResponse
    {
        $query = WalletTransaction::with('user');

        if ($data['date_from']) {
            $query->where('created_at', '>=', $data['date_from']);
        }

        if ($data['date_to']) {
            $query->where('created_at', '<=', $data['date_to']);
        }

        if ($data['type'] !== 'all') {
            $query->where('type', $data['type']);
        }

        $transactions = $query->get();

        $filename = 'wallet_transactions_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->stream(function () use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, ['ID', 'User', 'Type', 'Amount', 'Balance Before', 'Balance After', 'Description', 'Status', 'Created At']);
            
            // Data
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->id,
                    $transaction->user->name,
                    $transaction->type,
                    $transaction->amount,
                    $transaction->balance_before,
                    $transaction->balance_after,
                    $transaction->description,
                    $transaction->status,
                    $transaction->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function exportLoyaltyPoints(array $data): StreamedResponse
    {
        $query = LoyaltyPoints::with(['user', 'sourceRequest.service']);

        if ($data['date_from']) {
            $query->where('created_at', '>=', $data['date_from']);
        }

        if ($data['date_to']) {
            $query->where('created_at', '<=', $data['date_to']);
        }

        $points = $query->get();

        $filename = 'loyalty_points_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->stream(function () use ($points) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, ['ID', 'User', 'Points', 'Source Service', 'Created At']);
            
            // Data
            foreach ($points as $point) {
                fputcsv($file, [
                    $point->id,
                    $point->user->name,
                    $point->points,
                    $point->sourceRequest ? $point->sourceRequest->service->name : 'Manual',
                    $point->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function getMonthlyStats(Carbon $month): array
    {
        return [
            'users' => User::whereMonth('created_at', $month->month)->count(),
            'bookings' => ServiceRequest::whereMonth('created_at', $month->month)->count(),
            'revenue' => Payment::where('status', 'paid')
                ->whereMonth('created_at', $month->month)
                ->sum('amount'),
            'wallet_transactions' => WalletTransaction::whereMonth('created_at', $month->month)->count(),
        ];
    }

    private function getTopServices(): array
    {
        return Service::withCount('serviceRequests')
            ->orderBy('service_requests_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($service) {
                return [
                    'name' => $service->name,
                    'bookings' => $service->service_requests_count,
                    'revenue' => $service->serviceRequests()
                        ->whereHas('payments', function ($q) {
                            $q->where('status', 'paid');
                        })
                        ->sum('total_price'),
                ];
            })
            ->toArray();
    }

    private function getTopUsers(): array
    {
        return User::withCount('serviceRequests')
            ->orderBy('service_requests_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'name' => $user->name,
                    'bookings' => $user->service_requests_count,
                    'spent' => $user->serviceRequests()
                        ->whereHas('payments', function ($q) {
                            $q->where('status', 'paid');
                        })
                        ->sum('total_price'),
                    'loyalty_points' => $user->loyaltyPoints()->sum('points'),
                ];
            })
            ->toArray();
    }

    private function getRevenueTrends(): array
    {
        $trends = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $trends[] = [
                'month' => $date->format('M Y'),
                'revenue' => Payment::where('status', 'paid')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('amount'),
            ];
        }
        return $trends;
    }
}
