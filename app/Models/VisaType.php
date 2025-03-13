<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VisaType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'delai', 'note', 'documents', 'destination_id', 'adult_price', 'child_price'];

    protected $casts = [
        'documents' => 'array', // Ensures JSON data is converted to an array
    ];

    public function destinations()
    {
        return $this->belongsTo(Destination::class);
    }
}
