<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkOrderCalcExpense extends Model
{
    use HasFactory;

    protected $table = 'work_order_calc_expenses';

    protected $primaryKey = 'id';

    public $timestamps = false; // Since you're manually managing created_at

    protected $fillable = [
        'work_order_id',
        'driver_fee',
        'secondary_driver_fee',
        'delivery_fuel_price',
        'return_fuel_price',
        'load_fee',
        'unload_fee',
        'additional_fee',
        'note',
        'total',
        'created_at',
        'created_by',
    ];

    // Relationships (optional)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
