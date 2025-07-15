<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentsDeptsHistoriquesResource\Pages;
use App\Filament\Resources\PaymentsDeptsHistoriquesResource\RelationManagers;
use App\Models\PaymentsDeptsHistorique;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentsDeptsHistoriquesResource extends Resource
{
    protected static ?string $model = PaymentsDeptsHistorique::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options([
                        'payment' => 'Payment',
                        'debt' => 'Debt',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('note')
                    ->numeric()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('User'),
                Tables\Columns\TextColumn::make('amount')->sortable(),
                Tables\Columns\TextColumn::make('type')->sortable(),
                Tables\Columns\TextColumn::make('note')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentsDeptsHistoriques::route('/'),
            'create' => Pages\CreatePaymentsDeptsHistoriques::route('/create'),
            'edit' => Pages\EditPaymentsDeptsHistoriques::route('/{record}/edit'),
        ];
    }
}
