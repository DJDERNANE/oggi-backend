<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentsDeptsHistoriquesResource\Pages;
use App\Filament\Resources\PaymentsDeptsHistoriquesResource\RelationManagers;
use App\Models\PaymentsDeptsHistorique;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentsDeptsHistoriquesResource extends Resource
{
    protected static ?string $model = PaymentsDeptsHistorique::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options([
                        'payment' => 'Payment',
                        'debt' => 'Debt',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('note')
                    ->numeric()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('user.name')->label('User'),
            Tables\Columns\BadgeColumn::make('amount')
                ->colors([
                    'danger' => fn($record) => $record->type === 'debt',
                    'success' => fn($record) => $record->type === 'payment',
                ])
                ->sortable()
                ->money('USD'),
            Tables\Columns\BadgeColumn::make('type')
                ->colors([
                    'danger' => 'debt',
                    'success' => 'payment',
                ])
                ->sortable(),
            Tables\Columns\TextColumn::make('note')->sortable()->limit(30),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->description(fn($record) => $record->created_at->diffForHumans()),
            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            // Transaction type filter
            Tables\Filters\SelectFilter::make('type')
                ->options([
                    'payment' => 'Payments',
                    'debt' => 'Debts',
                ])
                ->label('Transaction Type'),
            
            // Amount range filter
            Tables\Filters\Filter::make('amount')
                ->form([
                    Forms\Components\TextInput::make('min_amount')
                        ->label('Min Amount')
                        ->numeric()
                        ->placeholder('Min amount'),
                    Forms\Components\TextInput::make('max_amount')
                        ->label('Max Amount')
                        ->numeric()
                        ->placeholder('Max amount'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['min_amount'],
                            fn (Builder $query, $value): Builder => $query->where('amount', '>=', $value)
                        )
                        ->when(
                            $data['max_amount'],
                            fn (Builder $query, $value): Builder => $query->where('amount', '<=', $value)
                        );
                }),
            
            // Date range filter
            Tables\Filters\Filter::make('created_at')
                ->form([
                    Forms\Components\DatePicker::make('created_from')->label('From Date'),
                    Forms\Components\DatePicker::make('created_until')->label('To Date'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['created_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date)
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date)
                        );
                }),
            
            // User filter with search
            Tables\Filters\SelectFilter::make('user_id')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->label('User'),
            
            // Note contains filter
            Tables\Filters\Filter::make('note')
                ->form([Forms\Components\TextInput::make('note')->label('Search in Notes')])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when(
                        $data['note'],
                        fn (Builder $query, $value): Builder => $query->where('note', 'like', "%{$value}%")
                    );
                }),
            
            // Recent transactions filter
            Tables\Filters\SelectFilter::make('recent')
                ->options([
                    'today' => 'Today',
                    'week' => 'This Week',
                    'month' => 'This Month',
                ])
                ->query(function (Builder $query, $data): Builder {
                    return $query->when(
                        $data['value'] === 'today',
                        fn (Builder $query): Builder => $query->whereDate('created_at', today())
                    )->when(
                        $data['value'] === 'week',
                        fn (Builder $query): Builder => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                    )->when(
                        $data['value'] === 'month',
                        fn (Builder $query): Builder => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                    );
                }),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\ViewAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ])
        ->defaultSort('created_at', 'desc');
}

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentsDeptsHistoriques::route('/'),
            'create' => Pages\CreatePaymentsDeptsHistoriques::route('/create'),
            'edit' => Pages\EditPaymentsDeptsHistoriques::route('/{record}/edit'),
        ];
    }
}
