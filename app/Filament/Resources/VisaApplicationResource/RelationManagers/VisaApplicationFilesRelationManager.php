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
                Forms\Components\TextInput::make('file_name')
                    ->label('File Description')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Passport Copy, Bank Statement'),

                Forms\Components\FileUpload::make('file_path')
                    ->label('File')
                    ->required()
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->maxSize(5120) // 5MB
                    ->directory('visa-application-files')
                    ->visibility('private')
                    ->downloadable()
                    ->previewable(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('file_name')
            ->columns([
                Tables\Columns\TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('file_path')
                    ->label('Download')
                    ->formatStateUsing(fn ($state) => 'Download File')
                    ->url(fn (VisaApplicationFile $record): ?string => 
                        $record->file_path ? route('visa.download', ['file' => $record->file_path]) : null
                    )
                    ->openUrlInNewTab()
                    ->disabled(fn (VisaApplicationFile $record): bool => empty($record->file_path))
                    ->color(fn (VisaApplicationFile $record): string => 
                        $record->file_path ? 'primary' : 'gray'
                    )
                    ->extraAttributes(fn (VisaApplicationFile $record): array => 
                        $record->file_path ? [] : ['class' => 'cursor-not-allowed opacity-50']
                    ),
                    
                Tables\Columns\IconColumn::make('file_path')
                    ->label('Has File')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add New File')
                    ->icon('heroicon-o-plus'),
                    
                Tables\Actions\Action::make('download_all')
                    ->label('Download All Files')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn () => route('visa.downloadAll', ['visaApplication' => $this->ownerRecord->id]))
                    ->disabled(fn (): bool => !$this->ownerRecord->visaApplicationFiles()->whereNotNull('file_path')->exists())
                    ->visible(fn (): bool => $this->ownerRecord->visaApplicationFiles()->whereNotNull('file_path')->exists()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit File'),
                    
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (VisaApplicationFile $record): ?string => 
                        $record->file_path ? route('visa.download', ['file' => $record->file_path]) : null
                    )
                    ->disabled(fn (VisaApplicationFile $record): bool => empty($record->file_path))
                    ->visible(fn (VisaApplicationFile $record): bool => !empty($record->file_path)),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('download_bulk')
                        ->label('Download Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        // ->action(fn ($records) => 
                        //     // You'll need to implement bulk download logic
                        //     // This could redirect to a route that handles multiple files
                        // )
                        // ->disabled(fn ($records) => $records->whereNotNull('file_path')->isEmpty()),
                ]),
            ])
            ->emptyStateHeading('No files uploaded yet')
            ->emptyStateDescription('Upload the first file by clicking the button below.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Upload File')
                    ->icon('heroicon-o-plus'),
            ]);
    }
}