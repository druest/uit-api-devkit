<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderReport extends Model
{
    use HasFactory;

    // Table name (optional if it matches plural of class name)
    protected $table = 'work_order_reports';

    // Primary key (optional if it's "id")
    protected $primaryKey = 'id';

    // Mass assignable fields
    protected $fillable = [
        'work_order_id',
        'report_details',
        'report_date',
        'reported_by',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
        'status',
        'created_by',
        'created_at',
        'updated_by',
        'updated_at',
    ];

    // If you want Laravel to manage timestamps automatically
    public $timestamps = false;
    // Set to true if you want Laravel to auto-fill created_at/updated_at

    // Relationships
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function pictures()
    {
        return $this->hasMany(WorkOrderReportPicture::class);
    }
}
