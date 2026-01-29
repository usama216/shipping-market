<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackingOptions extends Model
{
    protected $fillable = ['title', 'description', 'price', 'value', 'is_text_input'];
}
