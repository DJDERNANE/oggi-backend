<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisaApplicationResource\Pages;
use App\Filament\Resources\VisaApplicationResource\RelationManagers;
use App\Models\VisaApplication;
use App\Models\Destination;
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
            // Visa Application Information
            Forms\Components\Section::make('Visa Application Information')
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
                    Forms\Components\DatePicker::make('departure_date')
                        ->required()
                        ->minDate(now()),
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'processing' => 'Processing',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            'action_required' => 'Action Required',
                        ])
                        ->default('pending')
                        ->reactive()
                        ->required()
                        ->disablePlaceholderSelection(),
                ])
                ->columns(2),

            // User and Destination Information
            Forms\Components\Section::make('User & Destination Details')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('User')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive(),

                    Forms\Components\Select::make('destination_name')
                        ->label('Destination')
                        ->options(fn() => Destination::pluck('name', 'name')->toArray())
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->required()
                        ->default(function () {
                            return request('destination') ?? session('destination');
                        })
                        ->afterStateUpdated(function ($set) {
                            $set('visa_type_id', null);
                            $set('price', null);
                        }),

                    Forms\Components\Select::make('visa_type_id')
                        ->label('Visa Type')
                        ->relationship('visaType', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default(function () {
                            return request('visa_type') ?? session('visa_type');
                        })
                        ->getOptionLabelFromRecordUsing(fn (\App\Models\VisaType $record) => "{$record->name} - {$record->adult_price} USD")
                        ->afterStateUpdated(function ($set, $state) {
                            if ($state) {
                                $visaType = \App\Models\VisaType::find($state);
                                if ($visaType && $visaType->adult_price) {
                                    $set('price', $visaType->adult_price);
                                }
                            }
                        }),

                    Forms\Components\TextInput::make('price')
                        ->label('Visa Price (USD)')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->reactive()
                        ->afterStateUpdated(function ($set, $state) {
                            $set('payment_amount', $state);
                        }),
                ])
                ->columns(2),

            // Payment Information Section
            Forms\Components\Section::make('Payment Information')
                ->schema([
                    Forms\Components\Select::make('payment_method')
                        ->options([
                            'credit_card' => 'Credit Card',
                            'debit_card' => 'Debit Card',
                            'bank_transfer' => 'Bank Transfer',
                            'cash' => 'Cash',
                            'paypal' => 'PayPal',
                            'other' => 'Other',
                        ])
                        ->default('cash')
                        ->required()
                        ->reactive(),

                    Forms\Components\TextInput::make('payment_amount')
                        ->label('Amount Paid (USD)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->default(function ($get) {
                            return $get('price') ?? 0;
                        }),

                    Forms\Components\Select::make('payment_type')
                        ->options([
                            'payment' => 'Payment',
                            'debt' => 'Debt',
                        ])
                        ->default('payment')
                        ->required(),

                    Forms\Components\DatePicker::make('payment_date')
                        ->label('Payment Date')
                        ->default(now())
                        ->required(),

                    Forms\Components\TextInput::make('transaction_id')
                        ->label('Transaction ID')
                        ->maxLength(255)
                        ->required()
                        ->visible(fn($get) => $get('payment_method') !== 'cash'),

                    Forms\Components\Textarea::make('payment_note')
                        ->label('Payment Notes')
                        ->rows(2)
                        ->columnSpanFull()
                        ->placeholder('Additional payment information...'),
                ])
                ->columns(2),

            // Documents Section
            Forms\Components\Section::make('Documents')
                ->schema([
                    Forms\Components\FileUpload::make('visa_file')
                        ->label('Upload Visa File')
                        ->directory('visas')
                        ->disk('public')
                        ->visible(fn($get) => in_array($get('status'), ['approved', 'rejected'])),

                    Forms\Components\Repeater::make('required_documents')
                        ->schema([
                            Forms\Components\TextInput::make('document_name')
                                ->label('Document Name')
                                ->required(),

                            Forms\Components\Toggle::make('required')
                                ->label('Required')
                                ->default(false),
                        ])
                        ->addActionLabel('Add Document')
                        ->collapsible()
                        ->visible(fn($get) => $get('status') === 'action_required'),
                ]),
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
