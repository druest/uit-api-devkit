<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrderType extends Model
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

    // Casts for proper data types
    protected $casts = [
        'is_terminal' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships (optional, depending on your user model)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
