<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\VisaType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VisaApplicationRelationManager extends RelationManager
{
    protected static string $relationship = 'VisaApplications';

    public function form(Form $form): Form
      {
        return $form
            ->schema([
                // Personal Information Section
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('First Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter first name'),

                        Forms\Components\TextInput::make('fammily_name')
                            ->label('Family Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter family name'),

                        Forms\Components\TextInput::make('passport_number')
                            ->label('Passport Number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Enter passport number')
                            ->alphanum(),

                        Forms\Components\DatePicker::make('departure_date')
                            ->label('Departure Date')
                            ->required()
                            ->minDate(now())
                            ->displayFormat('Y-m-d')
                            ->native(false),
                    ])
                    ->columns(2),

                // Application Details Section
                Forms\Components\Section::make('Application Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Applicant')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Select::make('visa_type_id')
                            ->label('Visa Type')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->options(function () {
                                return VisaType::with('destination')
                                    ->get()
                                    ->mapWithKeys(function ($visaType) {
                                        $label = $visaType->destination
                                            ? "{$visaType->destination->name} - {$visaType->name} ({$visaType->adult_price} DZD)"
                                            : "{$visaType->name} ({$visaType->adult_price} DZD)";

                                        return [$visaType->id => $label];
                                    });
                            })
                            ->getOptionLabelFromRecordUsing(function (VisaType $record) {
                                $destination = $record->destination;
                                return $destination
                                    ? "{$destination->name} - {$record->name} ({$record->adult_price} DZD)"
                                    : "{$record->name} ({$record->adult_price} DZD)";
                            })
                            ->afterStateUpdated(function ($set, $state) {
                                if ($state) {
                                    $visaType = VisaType::find($state);
                                    if ($visaType) {
                                        $set('price', $visaType->adult_price);
                                        if (!request()->filled('destination_id')) {
                                            $set('destination_id', $visaType->destination_id);
                                        }
                                    }
                                } else {
                                    $set('price', 0);
                                }
                            }),

                        Forms\Components\TextInput::make('price')
                            ->label('Visa Price (DZD)')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->prefix('DZD')
                            ->reactive()
                            ->dehydrated(true),

                        Forms\Components\Select::make('status')
                            ->label('Application Status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'action_required' => 'Action Required',
                            ])
                            ->default('pending')
                            ->required()
                            ->disablePlaceholderSelection()
                            ->reactive(),
                    ])
                    ->columns(2),
                

            ]);

        // Also add this method to your Resource class (EditVisaApplication or VisaApplicationResource)

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
