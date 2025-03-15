<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $table = 'otp_codes';
    protected $fillable = ['user_id', 'sms_code', 'email_code', 'sms_expires_at', 'email_expires_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
