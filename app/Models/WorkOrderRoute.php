<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderRoute extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'work_order_routes';

    protected $fillable = [
        'work_order_request_id',
        'route_type',
        'address_name',
        'full_address',
        'lat',
        'lng',
        'created_by',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    // Relationships
    public function request()
    {
        return $this->belongsTo(WorkOrderRequest::class, 'work_order_request_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
