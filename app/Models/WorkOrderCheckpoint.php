<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderCheckpoint extends Model
{
    protected $table = 'work_order_checkpoints';

    protected $fillable = [
        'work_order_id',
        'cp1', 'cp2', 'cp3', 'cp4', 'cp5', 'cp6',
        'pic',
        'notes',
        'status',
        'created_by',
        'created_at',
    ];

    public $timestamps = false; // If you're manually handling created_at

    // Relationships
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Optional: Accessors or mutators for checkpoint logic
    public function getCheckpointStatus(): string
    {
        $completed = collect([$this->cp1, $this->cp2, $this->cp3, $this->cp4, $this->cp5, $this->cp6])
            ->filter(fn ($cp) => $cp === true || $cp === 1)
            ->count();

        return "Completed {$completed}/6";
    }
}
