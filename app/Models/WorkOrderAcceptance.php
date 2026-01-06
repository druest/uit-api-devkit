<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderAcceptance extends Model
{
    use HasFactory;

    // Explicit table name (since it's not the default plural form)
    protected $table = 'work_order_acceptances';
    public $timestamps = false;
    // Primary key
    protected $primaryKey = 'id';

    // Mass assignable fields
    protected $fillable = [
        'work_order_id',
        'acceptances_status',
        'acceptances_date',
        'remarks',
        'created_by',
    ];

    // Casts for automatic type conversion
    protected $casts = [
        'acceptances_date' => 'datetime',
    ];

    // Relationships
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
