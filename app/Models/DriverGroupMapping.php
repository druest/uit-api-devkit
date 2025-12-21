<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverGroupMapping extends Model
{
    // Table name (optional if it matches the plural form)
    protected $table = 'driver_group_mapping';

    // Primary key
    protected $primaryKey = 'id';

    // If your table doesn’t use updated_at, disable timestamps
    public $timestamps = false;

    // Mass assignable fields
    protected $fillable = [
        'driver_id',
        'group_id',
        'created_by',
        'created_at'
    ];

    // If created_at is not a standard timestamp, cast it
    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Example relationships
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function group()
    {
        return $this->belongsTo(CityDistributionGroup::class, 'group_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
