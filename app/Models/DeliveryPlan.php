<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeliveryPlan extends Model
{
    use HasFactory;

    protected $table = 'delivery_plans';

    protected $primaryKey = 'id';

    public $timestamps = false; // Set to true if you use Laravel's created_at/updated_at

    protected $fillable = [
        'delivery_id',
        'assignment_type',
        'unit_id',
        'vendor_id',
        'planner_notes',
        'next_city',
        'next_city_purpose',
        'created_by',
        'created_at',
    ];

    // Relationships (optional, based on your schema)
    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function nextCity()
    {
        return $this->belongsTo(City::class, 'next_city');
    }


    public function unit()
    {
        return $this->belongsTo(Unit::class);
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
