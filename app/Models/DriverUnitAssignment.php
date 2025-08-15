<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DriverUnitAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_date',
        'unit_id',
        'driver_id',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'assignment_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors
    public function getAssignmentLabelAttribute()
    {
        return "{$this->driver->name} → {$this->unit->plate_full} on {$this->assignment_date->format('d M Y')}";
    }
}
