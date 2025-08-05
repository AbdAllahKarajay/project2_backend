<?php

namespace App\Filament\Admin\Resources;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\Service;
use App\Models\Location;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    public static function getPages(): array
    {
        return [
            'index' => ServiceRequestResource\Pages\ListServiceRequests::route('/'),
            'create' => ServiceRequestResource\Pages\CreateServiceRequest::route('/create'),
            'edit' => ServiceRequestResource\Pages\EditServiceRequest::route('/{record}/edit'),
        ];
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'assigned' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'assigned' => 'Assigned',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('service_id')
                    ->label('Service')
                    ->options(Service::pluck('name', 'id')),
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
                Forms\Components\Section::make('Service Request Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Customer')
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('service_id')
                            ->label('Service')
                            ->options(Service::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('location_id')
                            ->label('Location')
                            ->options(Location::pluck('address_text', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'assigned' => 'Assigned',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),
                        Forms\Components\TextInput::make('total_price')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('special_instructions')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->collapsible(),
            ]);
    }
}