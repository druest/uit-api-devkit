<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VendorRoutePricing extends Model
{
    use HasFactory;

    protected $table = 'vendor_route_pricing';

    protected $primaryKey = 'id';

    public $timestamps = false; // Since you're manually managing created_at

    protected $fillable = [
        'vendor_id',
        'origin_city',
        'destination_city',
        'price',
        'type_car',
        'effective_date',
        'created_at',
        'created_by',
    ];

    protected $casts = [
        'price' => 'float',
        'effective_date' => 'date',
        'created_at' => 'datetime',
    ];

    // Relationships (optional)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}
