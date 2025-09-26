<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderEvent extends Model
{
    protected $table = 'work_order_events';

    protected $fillable = [
        'work_order_id',
        'delivery_phase_id',
        'target_time',
        'actual_time',
        'target_duration_in_seconds',
        'actual_duration_in_seconds',
        'actual_result',
        'created_source',
        'created_by',
        'created_date',
        'pic',
        'status',
        'remarks',
    ];

    public $timestamps = false;

    // Relationships
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function deliveryPhase(): BelongsTo
    {
        return $this->belongsTo(DeliveryPhase::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            $this->created_source === 'driver' ? Driver::class : User::class,
            'created_by'
        );
    }

    // Accessors
    public function getFormattedDurationAttributeActual(): string
    {
        $seconds = $this->actual_duration_in_seconds ?? 0;
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return trim("{$days}d {$hours}h {$minutes}m");
    }

    public function getFormattedDurationAttributeTarget(): string
    {
        $seconds = $this->target_duration_in_seconds ?? 0;
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return trim("{$days}d {$hours}h {$minutes}m");
    }

    // Enum helpers
    public static function resultOptions(): array
    {
        return ['not applicable', 'on time', 'overdue', 'early arrival'];
    }

    public static function statusOptions(): array
    {
        return ['not started', 'need confirmation', 'final'];
    }
}
