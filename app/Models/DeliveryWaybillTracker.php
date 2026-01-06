<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryWaybillTracker extends Model
{
    use HasFactory;

    protected $table = 'delivery_waybills_tracker';
    public $timestamps = false;

    protected $fillable = [
        'delivery_waybill_expedition_id',
        'status',
        'remarks',
        'created_by',
    ];

    // Relasi ke Waybill
    public function waybill()
    {
        return $this->belongsTo(DeliveryWaybillExpedition::class, 'delivery_waybill_expedition_id');
    }

    // Relasi ke User
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
