<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryStatus extends Model
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
    ];

    // 🧑 Creator relationship
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ✍️ Updater relationship
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // 🎯 Scope for terminal statuses
    public function scopeTerminal($query)
    {
        return $query->where('is_terminal', true);
    }

    // 🔍 Scope for finding by code
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    // 🎨 Optional: UI badge helper
    public function badge(): string
    {
        return "<span class='badge' style='background-color: {$this->color}'>{$this->label}</span>";
    }
}
