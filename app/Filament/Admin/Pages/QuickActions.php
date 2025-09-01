<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Payment;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class QuickActions extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'Quick Actions';

    protected static ?string $title = 'Quick Actions';

    protected static ?string $slug = 'quick-actions';

    protected static ?int $navigationSort = 2;

    public function getHeading(): string
    {
        return 'Quick Actions Dashboard';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_user')
                ->label('Create User')
                ->icon('heroicon-m-user-plus')
                ->color('success')
                ->form([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->email()
                        ->maxLength(255)
                        ->unique('users', 'email'),
                    TextInput::make('phone')
                        ->tel()
                        ->required()
                        ->maxLength(20)
                        ->unique('users', 'phone'),
                    TextInput::make('password')
                        ->password()
                        ->required()
                        ->minLength(8),
                    Select::make('role')
                        ->options([
                            'admin' => 'Admin',
                            'customer' => 'Customer',
                        ])
                        ->default('customer')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'],
                        'password' => bcrypt($data['password']),
                        'role' => $data['role'],
                    ]);

                    Notification::make()
                        ->title('User created successfully')
                        ->success()
                        ->send();
                }),

            Action::make('create_service')
                ->label('Create Service')
                ->icon('heroicon-m-wrench-screwdriver')
                ->color('info')
                ->form([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->rows(3)
                        ->maxLength(1000),
                    Select::make('category')
                        ->options([
                            'cleaning' => 'Cleaning',
                            'maintenance' => 'Maintenance',
                            'repair' => 'Repair',
                            'installation' => 'Installation',
                            'other' => 'Other',
                        ])
                        ->required(),
                    TextInput::make('base_price')
                        ->numeric()
                        ->prefix('$')
                        ->required()
                        ->step(0.01),
                    TextInput::make('duration_minutes')
                        ->numeric()
                        ->suffix('minutes')
                        ->step(1),
                ])
                ->action(function (array $data): void {
                    Service::create($data);

                    Notification::make()
                        ->title('Service created successfully')
                        ->success()
                        ->send();
                }),

            Action::make('wallet_refill')
                ->label('Wallet Refill')
                ->icon('heroicon-m-plus-circle')
                ->color('success')
                ->form([
                    Select::make('user_id')
                        ->label('User')
                        ->options(User::pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    TextInput::make('amount')
                        ->label('Amount')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->maxValue(10000.00)
                        ->prefix('$'),
                    Textarea::make('description')
                        ->label('Description')
                        ->required()
                        ->maxLength(500),
                    TextInput::make('reference')
                        ->label('Reference')
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    try {
                        DB::beginTransaction();

                        $user = User::findOrFail($data['user_id']);
                        $balanceBefore = $user->wallet_balance;
                        $amount = $data['amount'];

                        // Create wallet transaction record
                        $user->walletTransactions()->create([
                            'type' => 'topup',
                            'amount' => $amount,
                            'balance_before' => $balanceBefore,
                            'balance_after' => $balanceBefore + $amount,
                            'reference' => $data['reference'] ?? 'QUICK_REFILL',
                            'description' => $data['description'],
                            'metadata' => [
                                'admin_id' => auth()->id(),
                                'admin_name' => auth()->user()->name,
                                'refill_amount' => $amount,
                                'refill_reason' => $data['description'],
                            ],
                            'status' => 'completed',
                        ]);

                        // Update user's wallet balance
                        $user->addToWallet($amount);

                        DB::commit();

                        Notification::make()
                            ->title('Wallet refilled successfully')
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        DB::rollBack();
                        
                        Notification::make()
                            ->title('Failed to refill wallet')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('add_loyalty_points')
                ->label('Add Loyalty Points')
                ->icon('heroicon-m-gift')
                ->color('warning')
                ->form([
                    Select::make('user_id')
                        ->label('User')
                        ->options(User::pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    TextInput::make('points')
                        ->label('Points')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(10000),
                    Textarea::make('description')
                        ->label('Description')
                        ->required()
                        ->maxLength(500),
                    TextInput::make('source_request_id')
                        ->label('Service Request ID (Optional)')
                        ->numeric()
                        ->helperText('Leave empty for manual points addition'),
                ])
                ->action(function (array $data): void {
                    try {
                        DB::beginTransaction();

                        $user = User::findOrFail($data['user_id']);
                        $points = $data['points'];
                        $sourceRequestId = $data['source_request_id'] ?: null;

                        // Create loyalty points record
                        $user->addLoyaltyPoints($points, $sourceRequestId);

                        DB::commit();

                        Notification::make()
                            ->title("Added {$points} loyalty points successfully")
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        DB::rollBack();
                        
                        Notification::make()
                            ->title('Failed to add loyalty points')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'totalUsers' => User::count(),
            'totalServices' => Service::count(),
            'totalBookings' => ServiceRequest::count(),
            'totalRevenue' => Payment::where('status', 'paid')->sum('amount'),
            'pendingBookings' => ServiceRequest::where('status', 'pending')->count(),
            'completedBookings' => ServiceRequest::where('status', 'completed')->count(),
            'recentUsers' => User::latest()->take(5)->get(),
            'recentBookings' => ServiceRequest::with('user', 'service')->latest()->take(5)->get(),
        ];
    }
}
