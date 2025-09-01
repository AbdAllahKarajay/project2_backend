<?php

namespace App\Filament\Admin\Resources\LoyaltyPointsManagementResource\Pages;

use App\Filament\Admin\Resources\LoyaltyPointsManagementResource;
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

class ShowLoyaltyPointsManagement extends ViewRecord
{
    protected static string $resource = LoyaltyPointsManagementResource::class;

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
                                TextEntry::make('total_loyalty_points')
                                    ->label('Current Points')
                                    ->numeric()
                                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
                                TextEntry::make('created_at')
                                    ->label('Registered')
                                    ->dateTime(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Loyalty Statistics')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('loyaltyPoints_count')
                                    ->label('Total Point Entries')
                                    ->counts('loyaltyPoints'),
                                TextEntry::make('total_points_earned')
                                    ->label('Total Points Earned')
                                    ->state(fn ($record) => $record->loyaltyPoints()
                                        ->where('points', '>', 0)
                                        ->sum('points')),
                                TextEntry::make('total_points_spent')
                                    ->label('Total Points Spent')
                                    ->state(fn ($record) => abs($record->loyaltyPoints()
                                        ->where('points', '<', 0)
                                        ->sum('points'))),
                                TextEntry::make('loyaltyRewardRedemptions_count')
                                    ->label('Rewards Redeemed')
                                    ->counts('loyaltyRewardRedemptions'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->getRecord()->loyaltyPoints()
                    ->with('sourceRequest.service')
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('points')
                    ->label('Points')
                    ->numeric()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "+{$state}" : "{$state}"),
                Tables\Columns\TextColumn::make('sourceRequest.service.name')
                    ->label('Source Service')
                    ->default('Manual'),
                Tables\Columns\TextColumn::make('sourceRequest.id')
                    ->label('Service Request ID')
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('positive_points')
                    ->query(fn ($query) => $query->where('points', '>', 0))
                    ->label('Points Earned'),
                Tables\Filters\Filter::make('negative_points')
                    ->query(fn ($query) => $query->where('points', '<', 0))
                    ->label('Points Spent'),
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
