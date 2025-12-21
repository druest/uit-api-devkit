<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    // Table name (optional if it matches plural of class name)
    protected $table = 'cities';

    // Primary key
    protected $primaryKey = 'id';

    // Disable auto-incrementing if you're manually assigning IDs
    public $incrementing = false;

    // Use bigint for key type
    protected $keyType = 'int';

    // Timestamps
    public $timestamps = false;

    // Fillable fields
    protected $fillable = [
        'id',
        'name',
        'lat',
        'lng',
        'created_date',
        'created_by',
    ];

    // Casts
    protected $casts = [
        'id' => 'integer',
        'created_by' => 'integer',
        'created_date' => 'datetime',
    ];

    // Relationships (if needed)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function origins()
    {
        return $this->hasMany(Origin::class, 'city_id', 'id');
    }

    public function destinations()
    {
        return $this->hasMany(Destination::class, 'city_id', 'id');
    }

    public function customers()
    {
        return $this->hasManyThrough(Customer::class, Destination::class, 'city_id', 'id', 'id', 'customer_id');
    }
}
