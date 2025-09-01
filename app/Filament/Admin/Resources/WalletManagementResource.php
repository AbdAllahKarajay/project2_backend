<?php

namespace App\Filament\Admin\Resources;

use App\Models\User;
use App\Models\WalletTransaction;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use App\Filament\Admin\Resources\WalletManagementResource\Pages;

class WalletManagementResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = 'Financial Management';

    protected static ?string $navigationLabel = 'Wallet Management';

    protected static ?string $modelLabel = 'User Wallet';

    protected static ?string $pluralModelLabel = 'User Wallets';

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('wallet_balance')
                    ->money('USD')
                    ->sortable()
                    ->color(fn (float $state): string => $state > 0 ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('walletTransactions_count')
                    ->counts('walletTransactions')
                    ->label('Transactions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'customer' => 'Customer',
                    ]),
                Tables\Filters\Filter::make('has_balance')
                    ->query(fn ($query) => $query->where('wallet_balance', '>', 0))
                    ->label('Has Balance'),
                Tables\Filters\Filter::make('no_balance')
                    ->query(fn ($query) => $query->where('wallet_balance', '=', 0))
                    ->label('No Balance'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_transactions')
                    ->label('Transactions')
                    ->icon('heroicon-m-list-bullet')
                    ->url(fn (User $record): string => route('filament.admin.resources.wallet-management.show', $record))
                    ->color('info'),
                Tables\Actions\Action::make('refill_wallet')
                    ->label('Refill')
                    ->icon('heroicon-m-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(10000.00)
                            ->prefix('$'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->maxLength(500),
                        Forms\Components\TextInput::make('reference')
                            ->label('Reference')
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, User $record): void {
                        try {
                            DB::beginTransaction();

                            $balanceBefore = $record->wallet_balance;
                            $amount = $data['amount'];

                            // Create wallet transaction record
                            WalletTransaction::create([
                                'user_id' => $record->id,
                                'type' => 'topup',
                                'amount' => $amount,
                                'balance_before' => $balanceBefore,
                                'balance_after' => $balanceBefore + $amount,
                                'reference' => $data['reference'] ?? 'ADMIN_REFILL',
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
                            $record->addToWallet($amount);

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
                Tables\Actions\Action::make('deduct_wallet')
                    ->label('Deduct')
                    ->icon('heroicon-m-minus-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(10000.00)
                            ->prefix('$'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->maxLength(500),
                        Forms\Components\TextInput::make('reference')
                            ->label('Reference')
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, User $record): void {
                        if (!$record->hasSufficientBalance($data['amount'])) {
                            Notification::make()
                                ->title('Insufficient wallet balance')
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            DB::beginTransaction();

                            $balanceBefore = $record->wallet_balance;
                            $amount = $data['amount'];

                            // Create wallet transaction record
                            WalletTransaction::create([
                                'user_id' => $record->id,
                                'type' => 'deduction',
                                'amount' => $amount,
                                'balance_before' => $balanceBefore,
                                'balance_after' => $balanceBefore - $amount,
                                'reference' => $data['reference'] ?? 'ADMIN_DEDUCTION',
                                'description' => $data['description'],
                                'metadata' => [
                                    'admin_id' => auth()->id(),
                                    'admin_name' => auth()->user()->name,
                                    'deduction_amount' => $amount,
                                    'deduction_reason' => $data['description'],
                                ],
                                'status' => 'completed',
                            ]);

                            // Update user's wallet balance
                            $record->deductFromWallet($amount);

                            DB::commit();

                            Notification::make()
                                ->title('Amount deducted successfully')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            Notification::make()
                                ->title('Failed to deduct amount')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletManagement::route('/'),
            'show' => Pages\ShowWalletManagement::route('/{record}'),
        ];
    }
}
