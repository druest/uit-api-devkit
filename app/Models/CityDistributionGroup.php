<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CityDistributionGroup extends Model
{
    // Explicit table name
    protected $table = 'city_distribution_group';

    // Primary key
    protected $primaryKey = 'id';

    // Disable default timestamps (since you only have created_at)
    public $timestamps = false;

    // Mass assignable fields
    protected $fillable = [
        'name',
        'description',
        'created_by',
        'created_at'
    ];

    // Cast created_at to a proper datetime
    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
