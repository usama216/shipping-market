<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaInvoiceOptions extends Model
{
    protected $fillable = ['title', 'description', 'value', 'is_text_input'];
}
