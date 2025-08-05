<?php

namespace App\Filament\Admin\Resources;

use App\Models\Coupon;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    public static function getPages(): array
    {
        return [
            'index' => CouponResource\Pages\ListCoupons::route('/'),
            'create' => CouponResource\Pages\CreateCoupon::route('/create'),
            'edit' => CouponResource\Pages\EditCoupon::route('/{record}/edit'),
        ];
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'success',
                        'fixed' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->formatStateUsing(fn (Coupon $record): string => 
                        $record->type === 'percentage' ? "{$record->value}%" : "\${$record->value}"
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_spend')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('usage_limit')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed Amount',
                    ]),
                Tables\Filters\Filter::make('active')
                    ->query(fn ($query) => $query->where('expiry_date', '>', now())->orWhereNull('expiry_date'))
                    ->label('Active Coupons Only'),
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
                Forms\Components\Section::make('Coupon Information')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(50)
                            // ->uppercase()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('type')
                            ->options([
                                'percentage' => 'Percentage Discount',
                                'fixed' => 'Fixed Amount Discount',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\TextInput::make('value')
                            ->numeric()
                            ->required()
                            ->step(0.01)
                            ->prefix(fn ($get) => $get('type') === 'percentage' ? '' : '$')
                            ->suffix(fn ($get) => $get('type') === 'percentage' ? '%' : '')
                            ->minValue(0)
                            ->maxValue(fn ($get) => $get('type') === 'percentage' ? 100 : null),
                        Forms\Components\TextInput::make('min_spend')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0),
                        Forms\Components\DatePicker::make('expiry_date')
                            ->minDate(now()),
                        Forms\Components\TextInput::make('usage_limit')
                            ->numeric()
                            ->step(1)
                            ->minValue(1),
                    ])
                    ->columns(2),
            ]);
    }
}