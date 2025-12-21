<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerDeliveryType extends Model
{
    protected $table = 'customer_delivery_type';
    protected $fillable = [
        'customer_id',
        'type_name',
        'description',
        'is_active',
        'created_by',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
