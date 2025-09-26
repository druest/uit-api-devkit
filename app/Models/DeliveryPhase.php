<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPhase extends Model
{
    protected $table = 'delivery_phases';

    protected $fillable = [
        'name',
        'description',
        'sequence_order',
        'is_active',
        'created_date',
        'created_by',
    ];

    public $timestamps = false;

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence_order');
    }

    // Accessors
    public function getIsActiveLabelAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }
}
