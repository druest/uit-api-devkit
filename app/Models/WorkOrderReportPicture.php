<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderReportPicture extends Model
{
    use HasFactory;

    // Table name (optional if it matches plural of class name)
    protected $table = 'work_order_report_pictures';

    // Primary key (optional if it's "id")
    protected $primaryKey = 'id';

    // Mass assignable fields
    protected $fillable = [
        'work_order_report_id',
        'file_name',
        'file_path',
        'created_by',
        'created_at',
    ];

    // If you want Laravel to auto-manage timestamps, set this to true
    public $timestamps = false;

    // Relationships
    public function report()
    {
        return $this->belongsTo(WorkOrderReport::class, 'work_order_report_id');
    }
}
