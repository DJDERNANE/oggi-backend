<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentsDeptsHistorique extends Model
{
    protected $table = 'payments_depts_historiques';
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'note',
    ];

    protected static function booted()
    {
        static::created(function ($record) {
            $user = $record->user;
            if ($user) {
                if ($record->type === 'payment') {
                    $user->payments = ($user->payments ?? 0) + ($record->amount ?? 0);
                    $user->last_payment_time = now();
                } elseif ($record->type === 'debt') {
                    $user->debts = ($user->debts ?? 0) + ($record->amount ?? 0);
                    $user->last_debt_time = now();
                }
                $user->save();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
