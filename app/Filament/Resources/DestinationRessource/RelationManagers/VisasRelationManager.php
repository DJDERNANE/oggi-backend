<?php

namespace App\Filament\Resources\DestinationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class VisasRelationManager extends RelationManager
{
    protected static string $relationship = 'Visas';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),

                Forms\Components\TextInput::make('description'),

                Forms\Components\TextInput::make('delai')
                    ->required(),

                Forms\Components\TextInput::make('note'),

                Forms\Components\TextInput::make('adult_price')
                    ->required(),
                Forms\Components\TextInput::make('child_price'),

                // Repeater for dynamic document key-value pairs
                Repeater::make('documents')
                    ->schema([
                        TextInput::make('document_name')
                            ->label('Document Name')
                            ->required(),

                        Toggle::make('required')
                            ->label('Required')
                            ->default(false),
                    ])
                    ->addActionLabel('Add Document') // Custom button text
                    ->collapsible()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('VisaType')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('adult_price'),
                Tables\Columns\TextColumn::make('child_price'),
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
