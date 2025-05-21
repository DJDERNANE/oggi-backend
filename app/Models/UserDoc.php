<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class UserDoc extends Model
{
    protected $table = 'user_docs';
    protected $fillable = ['user_id', 'name', 'path', 'type'];
}
