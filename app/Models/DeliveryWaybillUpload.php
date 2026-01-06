<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryWaybillUpload extends Model
{
    use HasFactory;

    protected $table = 'delivery_waybill_upload';
    public $timestamps = false;

    protected $fillable = [
        'delivery_id',
        'driver_id',
        'upload_date',
        'file_name',
        'file_path',
        'delivery_phase_id',
        'created_by',
        'updated_by',
    ];

    // Relasi ke Delivery
    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'delivery_id');
    }

    // Relasi ke Driver
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    // Relasi ke Phase
    public function phase()
    {
        return $this->belongsTo(DeliveryPhase::class, 'delivery_phase_id');
    }

    // Relasi ke User (creator)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relasi ke User (updater)
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
