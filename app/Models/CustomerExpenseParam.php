<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerExpenseParam extends Model
{
    use HasFactory;

    protected $table = 'customer_expense_params';

    protected $primaryKey = 'id';

    public $timestamps = false; // Since you're manually managing created_at

    protected $fillable = [
        'customer_id',
        'driver_fee',
        'secondary_driver_fee',
        'fuel_price',
        'distance_fn',
        'delivery_fn',
        'return_fn',
        'load_fee',
        'unload_fee',
        'effective_date',
        'is_active',
        'created_by',
        'created_at',
        'is_round_trip',
    ];

    // Relationships (optional)
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
