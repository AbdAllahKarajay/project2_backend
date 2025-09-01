<?php

namespace App\Filament\Admin\Resources;

use App\Models\User;
use App\Models\LoyaltyPoints;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use App\Filament\Admin\Resources\LoyaltyPointsManagementResource\Pages;

class LoyaltyPointsManagementResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Loyalty System';

    protected static ?string $navigationLabel = 'Loyalty Points';

    protected static ?string $modelLabel = 'User Loyalty Points';

    protected static ?string $pluralModelLabel = 'User Loyalty Points';

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
                Tables\Columns\TextColumn::make('total_loyalty_points')
                    ->label('Total Points')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('loyaltyPoints_count')
                    ->counts('loyaltyPoints')
                    ->label('Point Entries')
                    ->sortable(),
                Tables\Columns\TextColumn::make('loyaltyRewardRedemptions_count')
                    ->counts('loyaltyRewardRedemptions')
                    ->label('Rewards Redeemed')
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
                Tables\Filters\Filter::make('has_points')
                    ->query(fn ($query) => $query->whereHas('loyaltyPoints', function ($q) {
                        $q->where('points', '>', 0);
                    }))
                    ->label('Has Points'),
                Tables\Filters\Filter::make('no_points')
                    ->query(fn ($query) => $query->whereDoesntHave('loyaltyPoints', function ($q) {
                        $q->where('points', '>', 0);
                    }))
                    ->label('No Points'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_points_history')
                    ->label('Points History')
                    ->icon('heroicon-m-list-bullet')
                    ->url(fn (User $record): string => route('filament.admin.resources.loyalty-points-management.show', $record))
                    ->color('info'),
                Tables\Actions\Action::make('add_points')
                    ->label('Add Points')
                    ->icon('heroicon-m-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('points')
                            ->label('Points')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(10000),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->maxLength(500),
                        Forms\Components\TextInput::make('source_request_id')
                            ->label('Service Request ID (Optional)')
                            ->numeric()
                            ->helperText('Leave empty for manual points addition'),
                    ])
                    ->action(function (array $data, User $record): void {
                        try {
                            DB::beginTransaction();

                            $points = $data['points'];
                            $sourceRequestId = $data['source_request_id'] ?: null;

                            // Create loyalty points record
                            $record->addLoyaltyPoints($points, $sourceRequestId);

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
                Tables\Actions\Action::make('deduct_points')
                    ->label('Deduct Points')
                    ->icon('heroicon-m-minus-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\TextInput::make('points')
                            ->label('Points')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(10000),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->maxLength(500),
                        Forms\Components\TextInput::make('source_request_id')
                            ->label('Service Request ID (Optional)')
                            ->numeric()
                            ->helperText('Leave empty for manual points deduction'),
                    ])
                    ->action(function (array $data, User $record): void {
                        if (!$record->hasEnoughPoints($data['points'])) {
                            Notification::make()
                                ->title('Insufficient loyalty points')
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            DB::beginTransaction();

                            $points = $data['points'];
                            $sourceRequestId = $data['source_request_id'] ?: null;

                            // Create loyalty points record (negative)
                            $record->deductLoyaltyPoints($points, $sourceRequestId);

                            DB::commit();

                            Notification::make()
                                ->title("Deducted {$points} loyalty points successfully")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            Notification::make()
                                ->title('Failed to deduct loyalty points')
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
            'index' => Pages\ListLoyaltyPointsManagement::route('/'),
            'show' => Pages\ShowLoyaltyPointsManagement::route('/{record}'),
        ];
    }
}
