<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VisaApplicationFile extends Model
{
    use HasFactory;
    protected $fillable = ['visa_application_id', 'file_path', 'name', 'type', 'size'];

    public function visaApplication()
    {
        return $this->belongsTo(VisaApplication::class);
    }
}
