<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VendorWorkOrder extends Model
{
    use HasFactory;

    protected $table = 'vendor_work_orders';

    protected $primaryKey = 'id';

    public $timestamps = false; // Since you're manually managing created_at

    protected $fillable = [
        'work_order_id',
        'vendor_id',
        'price',
        'total',
        'created_at',
        'created_by',
    ];

    protected $casts = [
        'price' => 'float',
        'total' => 'float',
        'created_at' => 'datetime',
    ];

    // Relationships (optional)
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
