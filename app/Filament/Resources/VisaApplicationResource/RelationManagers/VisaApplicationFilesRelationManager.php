<?php

namespace App\Filament\Resources\VisaApplicationResource\RelationManagers;

use App\Models\VisaApplicationFile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VisaApplicationFilesRelationManager extends RelationManager
{
    protected static string $relationship = 'visaApplicationFiles';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('Files')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Files')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Download Files')
                    ->url(fn (VisaApplicationFile $record) => route('visa.download', ['file' => $record->file_path])) // Define a route for downloading
                    ->openUrlInNewTab(),
                Tables\Columns\ImageColumn::make('file_path')
                    ->label('Document')
                    ->disk('public') 
                    ->size(50)
                    ->getStateUsing(fn (VisaApplicationFile $record) => asset('storage/' . $record->file_path))            
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('Download All')
                ->label('Download All Files')
                // ->icon('heroicon-o-download')
                ->url(fn () => route('visa.downloadAll', ['visaApplication' => $this->ownerRecord->id]))
                ->color('primary'),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    
}
