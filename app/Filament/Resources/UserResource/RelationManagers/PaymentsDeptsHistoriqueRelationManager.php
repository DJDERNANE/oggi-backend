<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentsDeptsHistoriqueRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentsDeptsHistoriques';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')->numeric()->required(),
                Forms\Components\Select::make('type')->options([
                    'payment' => 'Payment',
                    'debt' => 'Debt',
                ])->required(),
                Forms\Components\TextInput::make('note')->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\BadgeColumn::make('amount')
                    ->colors([
                        'danger' => fn($record) => $record->type === 'debt',
                        'success' => fn($record) => $record->type === 'payment',
                    ])
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'danger' => 'debt',
                        'success' => 'payment',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('note')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
} 