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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
