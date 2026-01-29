<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginOption extends Model
{
    protected $fillable = ['title', 'description', 'price', 'is_text_input'];
}
