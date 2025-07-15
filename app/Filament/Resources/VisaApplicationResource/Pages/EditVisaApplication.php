<?php

namespace App\Filament\Resources\VisaApplicationResource\Pages;

use App\Filament\Resources\VisaApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\PaymentsDeptsHistorique;

class EditVisaApplication extends EditRecord
{
    protected static string $resource = VisaApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        // Only act if status is being changed to 'processing' and was not already 'processing'
        if ($record->status !== 'processing' && $data['status'] === 'processing') {
            $user = $record->user;
            if ($user) {
                $user->debts = ($user->debts ?? 0) + ($record->price ?? 0);
                $user->save();
                PaymentsDeptsHistorique::create([
                    'user_id' => $user->id,
                    'amount' => $record->price ?? 0,
                    'type' => 'debt',
                    'note' => 'frais visa (' . $record->name . ')',
                ]);
            }
        }
        return $data;
    }
}
