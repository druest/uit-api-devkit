<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CityGroupMapping extends Model
{
    // Explicit table name
    protected $table = 'city_group_mapping';

    // Primary key
    protected $primaryKey = 'id';

    // Disable default timestamps (since you only have created_at)
    public $timestamps = false;

    // Mass assignable fields
    protected $fillable = [
        'city_id',
        'group_id',
        'created_by',
        'created_at'
    ];

    // Cast created_at to a proper datetime
    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relationships
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
