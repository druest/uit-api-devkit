<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryWaybill extends Model
{
    use HasFactory;

    // Table name (optional if it matches plural of class name)
    protected $table = 'delivery_waybills';

    // Primary key (optional if it's "id")
    protected $primaryKey = 'id';

    // Mass assignable fields
    protected $fillable = [
        'delivery_id',
        'type',
        'number',
        'quantity',
        'units',
        'file_name',
        'file_path',
        'remarks',
        'created_by',
        'created_at',
    ];

    // If you want Laravel to auto-manage timestamps, set this to true
    public $timestamps = false;

    // Relationships
    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'delivery_id');
    }
}
