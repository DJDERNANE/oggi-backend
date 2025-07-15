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
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->html()
                    ->getStateUsing(function($record) {
                        $balance = ($record->payments ?? 0) - ($record->debts ?? 0);
                        $color = $balance < 0 ? '#dc2626' : '#16a34a';
                        return new \Illuminate\Support\HtmlString("<span style='color: $color; font-weight: bold;'>" . abs($balance) . "</span>");
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('visaApplications.count')
                ->label('Pending Visa Applications')
            ])
            ->filters([
                //
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
