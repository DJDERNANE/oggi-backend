<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisaApplicationResource\Pages;
use App\Filament\Resources\VisaApplicationResource\RelationManagers;
use App\Models\VisaApplication;
use App\Models\Destination;
use App\Models\VisaType;
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
use App\Models\PaymentsDeptsHistorique;

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
    public function mutateFormDataBeforeFill(array $data): array
    {
        // Set destination_id from the visa type relationship if not already set
        if (!isset($data['destination_id']) && isset($data['visa_type_id'])) {
            $visaType = VisaType::find($data['visa_type_id']);
            if ($visaType) {
                $data['destination_id'] = $visaType->destination_id;
            }
        }

        return $data;
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
                // Text-based filters
                Tables\Filters\Filter::make('name')
                    ->form([Forms\Components\TextInput::make('name')])
                    ->query(function (Builder $query, array $data) {
                        return $query->when(
                            $data['name'],
                            fn($query, $name) =>
                            $query->where('name', 'like', "%{$name}%")
                        );
                    }),

                Tables\Filters\Filter::make('family_name')
                    ->form([Forms\Components\TextInput::make('family_name')])
                    ->query(function (Builder $query, array $data) {
                        return $query->when(
                            $data['family_name'],
                            fn($query, $familyName) =>
                            $query->where('fammily_name', 'like', "%{$familyName}%")
                        );
                    }),

                Tables\Filters\Filter::make('passport_number')
                    ->form([Forms\Components\TextInput::make('passport_number')])
                    ->query(function (Builder $query, array $data) {
                        return $query->when(
                            $data['passport_number'],
                            fn($query, $passport) =>
                            $query->where('passport_number', 'like', "%{$passport}%")
                        );
                    }),

                // Status filter (select dropdown)
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'in_review' => 'In Review',
                        // Add more status options as needed
                    ]),

                // Date range filters
                Tables\Filters\Filter::make('departure_date')
                    ->form([
                        Forms\Components\DatePicker::make('departure_from'),
                        Forms\Components\DatePicker::make('departure_to'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['departure_from'],
                                fn($query, $date) =>
                                $query->whereDate('departure_date', '>=', $date)
                            )
                            ->when(
                                $data['departure_to'],
                                fn($query, $date) =>
                                $query->whereDate('departure_date', '<=', $date)
                            );
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Submitted from'),
                        Forms\Components\DatePicker::make('created_to')
                            ->label('Submitted to'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn($query, $date) =>
                                $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['created_to'],
                                fn($query, $date) =>
                                $query->whereDate('created_at', '<=', $date)
                            );
                    }),

                // Ternary filter for boolean-like fields (if you had any)
                // Tables\Filters\TernaryFilter::make('is_active'),

                // Trashed filter (if you use soft deletes)
                // Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    // Tables\Actions\RestoreBulkAction::make(),
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
