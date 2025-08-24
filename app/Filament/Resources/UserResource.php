<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\VisaApplication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 2;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\DatePicker::make('phone_verified_at'),
                Forms\Components\TextInput::make('payments')->label('Total Payments')->disabled(),
                Forms\Components\TextInput::make('debts')->label('Total Debts')->disabled(),
                Forms\Components\Placeholder::make('balance')
                ->label('Balance')
                ->content(function ($record) {
                    $balance = ($record->payments ?? 0) - ($record->debts ?? 0);
                    $color = $balance < 0 ? '#dc2626' : '#16a34a';
                    return new HtmlString("<span style='color: $color; font-weight: bold;'>" . abs($balance) . "</span>");
                })
                ->extraAttributes(['style' => 'font-size: 1.2em;'])
                ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('last_payment_time')->label('Last Payment Time')->disabled(),
                Forms\Components\DateTimePicker::make('last_debt_time')->label('Last Debt Time')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->html()
                    ->getStateUsing(function($record) {
                        $balance = ($record->payments ?? 0) - ($record->debts ?? 0);
                        $color = $balance < 0 ? '#dc2626' : '#16a34a';
                        return new \Illuminate\Support\HtmlString(
                            "<span style='color: $color; font-weight: bold;'>" . 
                            number_format(abs($balance), 2) . 
                            "</span>"
                        );
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('visaApplications.count')
                    ->label('Pending Visa Applications')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Text filter for name
                Tables\Filters\Filter::make('name')
                    ->form([Forms\Components\TextInput::make('name')->label('Search Name')])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['name'],
                            fn (Builder $query, $value): Builder => $query->where('name', 'like', "%{$value}%")
                        );
                    }),
                
                // Email domain filter
                Tables\Filters\SelectFilter::make('email_domain')
                    ->options([
                        'gmail.com' => 'Gmail',
                        'yahoo.com' => 'Yahoo',
                        'outlook.com' => 'Outlook',
                        'hotmail.com' => 'Hotmail',
                    ])
                    ->query(function (Builder $query, $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $value): Builder => 
                                $query->where('email', 'like', "%@{$value}")
                        );
                    }),
                
                // Balance range filter
                Tables\Filters\Filter::make('balance_range')
                    ->form([
                        Forms\Components\TextInput::make('min_balance')
                            ->label('Min Balance')
                            ->numeric()
                            ->placeholder('Min value'),
                        Forms\Components\TextInput::make('max_balance')
                            ->label('Max Balance')
                            ->numeric()
                            ->placeholder('Max value'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_balance'],
                                fn (Builder $query, $value): Builder => 
                                    $query->whereRaw('(payments - debts) >= ?', [$value])
                            )
                            ->when(
                                $data['max_balance'],
                                fn (Builder $query, $value): Builder => 
                                    $query->whereRaw('(payments - debts) <= ?', [$value])
                            );
                    }),
                
                // Positive/Negative balance filter
                Tables\Filters\SelectFilter::make('balance_type')
                    ->options([
                        'positive' => 'Positive Balance',
                        'negative' => 'Negative Balance',
                    ])
                    ->query(function (Builder $query, $data): Builder {
                        return $query->when(
                            $data['value'] === 'positive',
                            fn (Builder $query): Builder => $query->whereRaw('(payments - debts) > 0')
                        )->when(
                            $data['value'] === 'negative',
                            fn (Builder $query): Builder => $query->whereRaw('(payments - debts) < 0')
                        );
                    }),
                
                // Date filter for creation date
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('From Date'),
                        Forms\Components\DatePicker::make('created_until')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date)
                            );
                    }),
                
                // Pending applications filter
                Tables\Filters\SelectFilter::make('pending_applications')
                    ->options([
                        '0' => 'None',
                        '1' => '1-5',
                        '2' => '6-10',
                        '3' => '10+',
                    ])
                    ->query(function (Builder $query, $data): Builder {
                        return $query->when(
                            $data['value'] === '0',
                            fn (Builder $query): Builder => $query->has('visaApplications', '=', 0)
                        )->when(
                            $data['value'] === '1',
                            fn (Builder $query): Builder => $query->has('visaApplications', 'between', [1, 5])
                        )->when(
                            $data['value'] === '2',
                            fn (Builder $query): Builder => $query->has('visaApplications', 'between', [6, 10])
                        )->when(
                            $data['value'] === '3',
                            fn (Builder $query): Builder => $query->has('visaApplications', '>', 10)
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    public static function getRelations(): array
    {
        return [
            RelationManagers\VisaApplicationRelationManager::class,
            RelationManagers\PaymentsDeptsHistoriqueRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    
}
