<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalType extends Model
{
    protected $table = 'usertypes';

    protected $fillable = [
        'name',
        'description',
    ];
}
