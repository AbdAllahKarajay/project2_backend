<?php

namespace App\Filament\Admin\Resources;

use App\Models\LoyaltyReward;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;

class LoyaltyRewardResource extends Resource
{
    protected static ?string $model = LoyaltyReward::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Loyalty System';

    public static function getPages(): array
    {
        return [
            'index' => LoyaltyRewardResource\Pages\ListLoyaltyRewards::route('/'),
            'create' => LoyaltyRewardResource\Pages\CreateLoyaltyReward::route('/create'),
            'edit' => LoyaltyRewardResource\Pages\EditLoyaltyReward::route('/{record}/edit'),
        ];
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'discount' => 'success',
                        'free_service' => 'info',
                        'upgrade' => 'warning',
                        'cashback' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('points_required')
                    ->numeric()
                    ->sortable()
                    ->suffix(' pts'),
                Tables\Columns\TextColumn::make('value')
                    ->formatStateUsing(fn (LoyaltyReward $record): string => 
                        $record->type === 'discount' ? "{$record->value}%" : 
                        ($record->value ? "\${$record->value}" : 'N/A')
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_redemptions')
                    ->numeric()
                    ->sortable()
                    ->suffix(fn (LoyaltyReward $record): string => 
                        $record->max_redemptions ? " / {$record->max_redemptions}" : ''
                    ),
                Tables\Columns\TextColumn::make('valid_from')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('valid_until')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'discount' => 'Discount',
                        'free_service' => 'Free Service',
                        'upgrade' => 'Service Upgrade',
                        'cashback' => 'Cash Back',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\Filter::make('available')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->label('Available Rewards Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Reward Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000),
                        Forms\Components\Select::make('type')
                            ->options([
                                'discount' => 'Discount',
                                'free_service' => 'Free Service',
                                'upgrade' => 'Service Upgrade',
                                'cashback' => 'Cash Back',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\TextInput::make('points_required')
                            ->numeric()
                            ->required()
                            ->step(1)
                            ->minValue(1)
                            ->suffix('points'),
                        Forms\Components\TextInput::make('value')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->prefix(fn ($get) => $get('type') === 'discount' ? '' : '$')
                            ->suffix(fn ($get) => $get('type') === 'discount' ? '%' : '')
                            ->maxValue(fn ($get) => $get('type') === 'discount' ? 100 : null)
                            ->visible(fn ($get) => in_array($get('type'), ['discount', 'upgrade', 'cashback'])),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Redemption Settings')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->helperText('Optional unique code for this reward'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\TextInput::make('max_redemptions')
                            ->numeric()
                            ->step(1)
                            ->minValue(1)
                            ->helperText('Leave empty for unlimited redemptions'),
                        Forms\Components\TextInput::make('current_redemptions')
                            ->numeric()
                            ->step(1)
                            ->minValue(0)
                            ->default(0)
                            ->disabled()
                            ->helperText('Current number of redemptions'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Validity Period')
                    ->schema([
                        Forms\Components\DatePicker::make('valid_from')
                            ->label('Valid From')
                            ->helperText('Leave empty to start immediately'),
                        Forms\Components\DatePicker::make('valid_until')
                            ->label('Valid Until')
                            ->helperText('Leave empty for no expiration'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}

