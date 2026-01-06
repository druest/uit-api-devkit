<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverLoginData extends Model
{
    use HasFactory;

    // Table name (if not following Laravel's plural convention)
    protected $table = 'driver_login_data';
    public $timestamps = false;

    // Primary key
    protected $primaryKey = 'id';

    // Mass assignable fields
    protected $fillable = [
        'driver_id',
        'token',
        'login_date',
        'expired_date',
        'status',
        'created_by',
    ];

    // Casts for automatic type conversion
    protected $casts = [
        'login_date'   => 'datetime',
        'expired_date' => 'datetime',
        'status'       => 'boolean',
    ];

    // Relationships
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}
