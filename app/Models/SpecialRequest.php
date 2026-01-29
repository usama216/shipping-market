<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialRequest extends Model
{
    protected $fillable = ['title', 'type', 'description', 'price'];
}
