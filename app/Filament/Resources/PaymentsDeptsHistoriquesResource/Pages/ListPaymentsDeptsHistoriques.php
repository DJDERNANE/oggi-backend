<?php

namespace App\Filament\Resources\PaymentsDeptsHistoriquesResource\Pages;

use App\Filament\Resources\PaymentsDeptsHistoriquesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentsDeptsHistoriques extends ListRecords
{
    protected static string $resource = PaymentsDeptsHistoriquesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
