<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DestinationResource\Pages;
use App\Filament\Resources\DestinationResource\RelationManagers;
use App\Models\Destination;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DestinationResource extends Resource
{
    protected static ?string $model = Destination::class;
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('code')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('description')
                ->maxLength(255),
                Forms\Components\FileUpload::make('flag')
                ->label('Upload country Flag')
                ->directory('flags') // stored in storage/app/visas
                ->disk('public') // optional: default disk
            ]);
    }

  public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('code')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('description')
                ->searchable()
                ->limit(50) // Limit description length
                ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                    $state = $column->getState();
                    if (strlen($state) <= 50) {
                        return null;
                    }
                    return $state; // Show full description on hover
                }),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            // Text filter for name
            Tables\Filters\Filter::make('name_filter')
                ->form([
                    Forms\Components\TextInput::make('name')
                        ->label('Search by Name')
                        ->placeholder('Enter name...')
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when(
                        $data['name'],
                        fn (Builder $query, $value): Builder => $query->where('name', 'like', "%{$value}%")
                    );
                }),
            
            // Text filter for code
            Tables\Filters\Filter::make('code_filter')
                ->form([
                    Forms\Components\TextInput::make('code')
                        ->label('Search by Code')
                        ->placeholder('Enter code...')
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when(
                        $data['code'],
                        fn (Builder $query, $value): Builder => $query->where('code', 'like', "%{$value}%")
                    );
                }),
            
           
           
              
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\ViewAction::make(), // Added view action
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            
            ]),
        ])
        ->defaultSort('name', 'asc'); // Default sorting
}

    public static function getRelations(): array
    {
        return [
            RelationManagers\VisasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDestinations::route('/'),
            'create' => Pages\CreateDestination::route('/create'),
            'edit' => Pages\EditDestination::route('/{record}/edit'),
        ];
    }
}
