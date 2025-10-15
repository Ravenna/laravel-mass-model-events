<?php

namespace Ravenna\MassModelEvents\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Ravenna\MassModelEvents\HasMassModelEvents;

class User extends Model
{
    use HasMassModelEvents;

    protected $fillable = [
        'name',
    ];
}