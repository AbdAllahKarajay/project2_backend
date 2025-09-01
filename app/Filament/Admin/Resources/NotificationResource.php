<?php

namespace App\Filament\Admin\Resources;

use App\Models\User;
use App\Models\Notification;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Filament\Admin\Resources\NotificationResource\Pages;


class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $modelLabel = 'Notification';

    protected static ?string $pluralModelLabel = 'Notifications';

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'email' => 'success',
                        'sms' => 'info',
                        'push' => 'warning',
                        'in_app' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('recipient_count')
                    ->label('Recipients')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'scheduled' => 'warning',
                        'sending' => 'info',
                        'sent' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled For')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'push' => 'Push Notification',
                        'in_app' => 'In-App',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'sending' => 'Sending',
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\Filter::make('scheduled')
                    ->query(fn ($query) => $query->whereNotNull('scheduled_at'))
                    ->label('Scheduled Only'),
            ])
            ->actions([
                Tables\Actions\Action::make('send_now')
                    ->label('Send Now')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(function ($record): void {
                        try {
                            $this->sendNotification($record);
                            
                            FilamentNotification::make()
                                ->title('Notification sent successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            FilamentNotification::make()
                                ->title('Failed to send notification')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->modalContent(fn ($record) => view('filament.admin.notifications.preview', ['notification' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
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
                Forms\Components\Section::make('Notification Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('message')
                            ->required()
                            ->rows(4)
                            ->maxLength(1000),
                        Forms\Components\Select::make('type')
                            ->options([
                                'email' => 'Email',
                                'sms' => 'SMS',
                                'push' => 'Push Notification',
                                'in_app' => 'In-App',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'normal' => 'Normal',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->default('normal')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Recipients')
                    ->schema([
                        Forms\Components\Select::make('recipient_type')
                            ->options([
                                'all' => 'All Users',
                                'role' => 'By Role',
                                'specific' => 'Specific Users',
                                'filtered' => 'Filtered Users',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\Select::make('recipient_role')
                            ->options([
                                'admin' => 'Admin',
                                'customer' => 'Customer',
                            ])
                            ->visible(fn ($get) => $get('recipient_type') === 'role')
                            ->required(),
                        Forms\Components\Select::make('recipient_users')
                            ->multiple()
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn ($get) => $get('recipient_type') === 'specific')
                            ->required(),
                        Forms\Components\TextInput::make('recipient_count')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Number of users who will receive this notification'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Scheduling')
                    ->schema([
                        Forms\Components\Toggle::make('is_scheduled')
                            ->label('Schedule for later')
                            ->reactive(),
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->visible(fn ($get) => $get('is_scheduled'))
                            ->required(fn ($get) => $get('is_scheduled'))
                            ->minDate(now()),
                        Forms\Components\Select::make('timezone')
                            ->options([
                                'UTC' => 'UTC',
                                'America/New_York' => 'Eastern Time',
                                'America/Chicago' => 'Central Time',
                                'America/Denver' => 'Mountain Time',
                                'America/Los_Angeles' => 'Pacific Time',
                            ])
                            ->default('UTC')
                            ->visible(fn ($get) => $get('is_scheduled')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Advanced Settings')
                    ->schema([
                        Forms\Components\TextInput::make('category')
                            ->maxLength(100)
                            ->helperText('Optional category for organizing notifications'),
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Additional Data')
                            ->helperText('Key-value pairs for custom notification data'),
                        Forms\Components\Toggle::make('track_opens')
                            ->label('Track opens')
                            ->default(true)
                            ->visible(fn ($get) => $get('type') === 'email'),
                        Forms\Components\Toggle::make('track_clicks')
                            ->label('Track clicks')
                            ->default(true)
                            ->visible(fn ($get) => $get('type') === 'email'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
            'create' => Pages\CreateNotification::route('/create'),
            'edit' => Pages\EditNotification::route('/{record}/edit'),
        ];
    }

    private function sendNotification($notification): void
    {
        // This is a simplified implementation
        // In a real application, you'd use a proper notification system
        
        $recipients = $this->getRecipients($notification);
        
        foreach ($recipients as $user) {
            // Send notification based on type
            switch ($notification->type) {
                case 'email':
                    $this->sendEmailNotification($user, $notification);
                    break;
                case 'sms':
                    $this->sendSmsNotification($user, $notification);
                    break;
                case 'push':
                    $this->sendPushNotification($user, $notification);
                    break;
                case 'in_app':
                    $this->sendInAppNotification($user, $notification);
                    break;
            }
        }

        // Update notification status
        $notification->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    private function getRecipients($notification): array
    {
        switch ($notification->recipient_type) {
            case 'all':
                return User::all()->toArray();
            case 'role':
                return User::where('role', $notification->recipient_role)->get()->toArray();
            case 'specific':
                return User::whereIn('id', $notification->recipient_users)->get()->toArray();
            default:
                return [];
        }
    }

    private function sendEmailNotification($user, $notification): void
    {
        // In a real implementation, you'd use Laravel's Mail system
        // Mail::to($user['email'])->send(new NotificationMail($notification));
    }

    private function sendSmsNotification($user, $notification): void
    {
        // In a real implementation, you'd use an SMS service
        // SMS::send($user['phone'], $notification->message);
    }

    private function sendPushNotification($user, $notification): void
    {
        // In a real implementation, you'd use a push notification service
        // PushNotification::send($user['device_token'], $notification->title, $notification->message);
    }

    private function sendInAppNotification($user, $notification): void
    {
        // In a real implementation, you'd store this in the database
        // InAppNotification::create([...]);
    }
}
