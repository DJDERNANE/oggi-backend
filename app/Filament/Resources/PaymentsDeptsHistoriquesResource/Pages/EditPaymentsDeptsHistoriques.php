<?php

namespace App\Filament\Resources\PaymentsDeptsHistoriquesResource\Pages;

use App\Filament\Resources\PaymentsDeptsHistoriquesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentsDeptsHistoriques extends EditRecord
{
    protected static string $resource = PaymentsDeptsHistoriquesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
