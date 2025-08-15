<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrderStatus extends Model
{
    protected $fillable = [
        'code',
        'label',
        'color',
        'notes',
        'is_terminal',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_terminal' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships to user model (assuming User exists)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scope for terminal statuses
    public function scopeTerminal($query)
    {
        return $query->where('is_terminal', true);
    }
}
