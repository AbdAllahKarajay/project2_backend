<?php

namespace App\Filament\Admin\Resources;

use App\Models\Service;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    public static function getPages(): array
    {
        return [
            'index' => ServiceResource\Pages\ListServices::route('/'),
            'create' => ServiceResource\Pages\CreateService::route('/create'),
            'edit' => ServiceResource\Pages\EditService::route('/{record}/edit'),
        ];
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->formatStateUsing(fn (int $state): string => $state ? "{$state} min" : 'N/A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('average_rating')
                    ->formatStateUsing(fn (float $state): string => number_format($state, 1))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'cleaning' => 'Cleaning',
                        'maintenance' => 'Maintenance',
                        'repair' => 'Repair',
                        'installation' => 'Installation',
                        'other' => 'Other',
                    ]),
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
                Forms\Components\Section::make('Service Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('category')
                            ->options([
                                'cleaning' => 'Cleaning',
                                'maintenance' => 'Maintenance',
                                'repair' => 'Repair',
                                'installation' => 'Installation',
                                'other' => 'Other',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000),
                        Forms\Components\TextInput::make('base_price')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->step(0.01),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->numeric()
                            ->suffix('minutes')
                            ->step(1),
                        Forms\Components\TextInput::make('average_rating')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(5)
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }
}