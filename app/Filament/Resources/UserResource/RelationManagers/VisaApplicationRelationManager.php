<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VisaApplicationRelationManager extends RelationManager
{
    protected static string $relationship = 'VisaApplications';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('visa')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
        ->recordTitleAttribute('id') // ✅ Use ID as the title
        ->columns([
            Tables\Columns\TextColumn::make('visaType.destination.name') // ✅ Reference 'id' directly
                ->label('Destination')
                ->sortable(),

            Tables\Columns\TextColumn::make('visaType.name') // ✅ Display visa type
                ->label('Visa Type'),

            Tables\Columns\TextColumn::make('status') // ✅ Show visa status
                ->label('Status')
                ->badge(),
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
