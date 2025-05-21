<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VisaApplication extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'fammily_name',
        'passport_number',
        'departure_date',
        'visa_type_id',
        'user_id',
        'status',
        'price',  
        'visa_file',
        'required_documents',
    ];

    public function visaApplicationFiles()
    {
        return $this->hasMany(VisaApplicationFile::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function visaType()
    {
        return $this->belongsTo(VisaType::class);
    }

    public function count()
    {
        return $this->where('status', 'pending')->count();
    }
}
