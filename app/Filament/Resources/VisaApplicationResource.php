<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisaApplicationResource\Pages;
use App\Filament\Resources\VisaApplicationResource\RelationManagers;
use App\Models\VisaApplication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class VisaApplicationResource extends Resource
{
    protected static ?string $model = VisaApplication::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::query()->where('status', 'pending')->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('fammily_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('passport_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('departure_date')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'action_required' => 'Action Required',

                    ])->reactive(),

                Forms\Components\FileUpload::make('visa_file')
                    ->label('Upload Visa')
                    ->directory('visas') // stored in storage/app/visas
                    ->disk('public') // optional: default disk
                    ->visible(fn($get) => $get('status') === 'approved'),
                // Repeater for dynamic document key-value pairs
                Repeater::make('required_documents')
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
                    ->required()->visible(fn($get) => $get('status') === 'action_required'),

                Forms\Components\TextInput::make('price'),
                Forms\Components\TextInput::make('destination_name')
                    ->disabled()
                    ->afterStateHydrated(function ($set, $record) {
                        if ($record && $record->visaType && $record->visaType->destination) {
                            $set('destination_name', $record->visaType->destination->name);
                        }
                    }),

                Forms\Components\Select::make('visa_type_id')
                    ->label('Visa Type')
                    ->relationship(
                        'visaType',
                        'name',
                        fn($query, $get) => $query->whereHas('destination', fn($q) => $q->where('name', $get('destination_name'))) // Filter by destination name
                    )
                    ->searchable()
                    ->preload()
                    ->reactive() // Ensures dynamic updates
                    ->afterStateUpdated(fn($set, $state) => $set(
                        'destination_name',
                        \App\Models\VisaType::find($state)?->destination?->name
                    ))
                    ->required(),





            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('fammily_name'),
                Tables\Columns\TextColumn::make('passport_number'),
                Tables\Columns\TextColumn::make('departure_date'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Submitted at'),
            ])
            ->defaultSort('created_at', 'desc')
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
            RelationManagers\VisaApplicationFilesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisaApplications::route('/'),
            'create' => Pages\CreateVisaApplication::route('/create'),
            'edit' => Pages\EditVisaApplication::route('/{record}/edit'),
        ];
    }
}
