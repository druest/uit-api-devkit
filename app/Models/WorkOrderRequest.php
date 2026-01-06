<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderRequest extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'work_order_requests';

    protected $fillable = [
        'number',
        'unit_id',
        'driver_id',
        'schedule',
        'purpose',
        'distance_value',
        'distance_display',
        'distance_calc_value',
        'distance_calc_display',
        'duration_value',
        'duration_display',
        'duration_calc_value',
        'duration_calc_display',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'schedule' => 'datetime',
        'distance_value' => 'decimal:2',
        'distance_calc_value' => 'decimal:2',
        'duration_value' => 'decimal:2',
        'duration_calc_value' => 'decimal:2',
    ];

    // Example relationships
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function routes()
    {
        return $this->hasMany(WorkOrderRoute::class, 'work_order_request_id');
    }

    public function expense()
    {
        return $this->hasMany(WorkOrderRequestExpense::class, 'work_order_request_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
