<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitGroupMapping extends Model
{
    // Table name (optional if it matches the plural form)
    protected $table = 'unit_group_mapping';

    // Primary key
    protected $primaryKey = 'id';

    // If your table doesn’t use updated_at, disable timestamps
    public $timestamps = false;

    // Mass assignable fields
    protected $fillable = [
        'unit_id',
        'group_id',
        'created_by',
        'created_at'
    ];

    // If created_at is not a standard timestamp, cast it
    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Example relationships
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
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
