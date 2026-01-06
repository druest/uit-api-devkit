<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'work_order_type_id',
        'delivery_id',
        'unit_id',
        'old_unit',
        'driver_id',
        'secondary_driver_id',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'work_order_status',
        'old_driver',
        'old_secondary_driver',
        'change_notes',
        'notes',
        'uj_type',
        'unit_notes',
        'di_notes',
        'sla',
        'is_fleet_unit',
        'is_fleet_driver',
        'is_fleet_second_driver',
        'assigned_at',
        'trf_available',
        'completed_at',
        'created_by',
        'updated_by',
        'payment_status',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function type(): BelongsTo
    {
        return $this->belongsTo(WorkOrderType::class, 'work_order_type_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(WorkOrderExpense::class);
    }

    public function workOrderEvents(): HasMany
    {
        return $this->hasMany(WorkOrderEvent::class);
    }

    public function otherExpenses(): HasMany
    {
        return $this->hasMany(WorkOrderOtherExpense::class);
    }

    public function getTotalExpenseAttribute(): float
    {
        return $this->expenses()->sum('amount');
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class, 'delivery_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function old_unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'old_unit');
    }


    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function second_driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'secondary_driver_id');
    }

    public function old_driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'old_driver');
    }

    public function old_second_driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'old_secondary_driver');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(WorkOrderStatus::class, 'work_order_status');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function vendorwo()
    {
        return $this->hasOne(VendorWorkOrder::class);
    }

    public function woCal()
    {
        return $this->hasMany(WorkOrderCalcExpense::class, 'work_order_id');
    }

    public function checkpoints()
    {
        return $this->hasMany(WorkOrderCheckpoint::class);
    }

    public function reports()
    {
        return $this->hasMany(WorkOrderReport::class);
    }

    public function acceptance()
    {
        return $this->hasOne(WorkOrderAcceptance::class, 'work_order_id');
    }
}
