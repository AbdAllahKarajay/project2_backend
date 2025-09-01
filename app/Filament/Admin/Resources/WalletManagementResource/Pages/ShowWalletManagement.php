<?php

namespace App\Filament\Admin\Resources\WalletManagementResource\Pages;

use App\Filament\Admin\Resources\WalletManagementResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;

class ShowWalletManagement extends ViewRecord
{
    protected static string $resource = WalletManagementResource::class;

    use InteractsWithTable;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('User Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name'),
                                TextEntry::make('email')
                                    ->label('Email'),
                                TextEntry::make('phone')
                                    ->label('Phone'),
                                TextEntry::make('role')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'admin' => 'danger',
                                        'customer' => 'success',
                                        default => 'gray',
                                    }),
                                TextEntry::make('wallet_balance')
                                    ->label('Current Balance')
                                    ->money('USD')
                                    ->color(fn (float $state): string => $state > 0 ? 'success' : 'gray'),
                                TextEntry::make('created_at')
                                    ->label('Registered')
                                    ->dateTime(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Wallet Statistics')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('walletTransactions_count')
                                    ->label('Total Transactions')
                                    ->counts('walletTransactions'),
                                TextEntry::make('total_topups')
                                    ->label('Total Top-ups')
                                    ->state(fn ($record) => $record->walletTransactions()
                                        ->where('type', 'topup')
                                        ->sum('amount'))
                                    ->money('USD'),
                                TextEntry::make('total_payments')
                                    ->label('Total Payments')
                                    ->state(fn ($record) => $record->walletTransactions()
                                        ->where('type', 'payment')
                                        ->sum('amount'))
                                    ->money('USD'),
                                TextEntry::make('total_refunds')
                                    ->label('Total Refunds')
                                    ->state(fn ($record) => $record->walletTransactions()
                                        ->where('type', 'refund')
                                        ->sum('amount'))
                                    ->money('USD'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->getRecord()->walletTransactions()
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'topup' => 'success',
                        'payment' => 'danger',
                        'refund' => 'info',
                        'bonus' => 'warning',
                        'deduction' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance_before')
                    ->money('USD')
                    ->label('Balance Before'),
                Tables\Columns\TextColumn::make('balance_after')
                    ->money('USD')
                    ->label('Balance After'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50),
                Tables\Columns\TextColumn::make('reference')
                    ->copyable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'topup' => 'Top Up',
                        'payment' => 'Payment',
                        'refund' => 'Refund',
                        'bonus' => 'Bonus',
                        'deduction' => 'Deduction',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
