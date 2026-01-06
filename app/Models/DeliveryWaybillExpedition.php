<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryWaybillExpedition extends Model
{
    use HasFactory;

    protected $table = 'delivery_waybill_expeditions';
    public $timestamps = false;
    protected $fillable = [
        'delivery_id',
        'courier',
        'tracking_number',
        'sent_by',
        'sent_date',
        'remarks',
        'created_by',
    ];

    // Relasi ke Delivery
    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'delivery_id');
    }

    public function tracker()
    {
        return $this->hasMany(DeliveryWaybillTracker::class, 'delivery_waybill_expedition_id');
    }

    // Relasi ke User (pengirim)
    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    // Relasi ke User (creator)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
