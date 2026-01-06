<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitPlanningWorksheet extends Model
{
    use HasFactory;

    protected $table = 'unit_planning_worksheet';

    protected $fillable = [
        'unit_id',
        'date',
        'plan_data',
        'delivery_id',
        'maintenance_id',
        'remarks',
        'created_by',
        'updated_by',
    ];

    // Relasi ke Unit
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    // Relasi ke Delivery
    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'delivery_id');
    }

    // // Relasi ke Maintenance
    // public function maintenance()
    // {
    //     return $this->belongsTo(Maintenance::class, 'maintenance_id');
    // }

    // Relasi ke User (creator)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relasi ke User (updater)
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
