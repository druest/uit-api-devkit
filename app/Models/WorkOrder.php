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
        'driver_id',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'work_order_status',
        'notes',
        'sla',
        'assigned_at',
        'completed_at',
        'created_by',
        'updated_by',
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

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
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
}
