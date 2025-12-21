<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    protected $fillable = [
        'delivery_date',
        'delivery_code',
        'customer_delivery_number',
        'confirmation_date',
        'customer_id',
        'delivery_type_id',
        'is_use_secondary_pricing',
        'assignment_type',
        'payment_type',
        'payment_due_date',
        'remarks',
        'status_id',
        'created_by',
        'updated_by',
        'notes',
    ];

    // Relationships
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function termsconditions(): HasMany
    {
        return $this->hasMany(DeliveryTermsCondition::class);
    }

    public function deliveryProblem(): HasMany
    {
        return $this->hasMany(DeliveryProblemNote::class);
    }

    public function deliveryType(): BelongsTo
    {
        return $this->belongsTo(DeliveryType::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(DeliveryStatus::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function routes()
    {
        return $this->hasMany(DeliveryRoute::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveryPlan()
    {
        return $this->hasOne(DeliveryPlan::class, 'delivery_id');
    }
}
