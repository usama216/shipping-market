<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    const STATUS_SUCCESS = 1;
    const STATUS_CANCELED = 2;
    const STATUS_REFUND = 3;

    protected $fillable = [
        'user_id',
        'customer_id',
        'status',
        'card',
        'amount',
        'description',
        'transaction_id',
        'transaction_date'
    ];

    protected $appends = ['status_name'];

    public function getStatusNameAttribute()
    {
        switch ($this->status) {
            case 1:
                return "Success";
            case 2:
                return "Failed";
            case 3:
                return "Refunded";
            default:
                return "Invalid status code";
        }
    }

    /**
     * @deprecated Use customer() instead for customer transactions
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the customer who owns this transaction
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
}

