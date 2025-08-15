<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    // If you're using soft deletes, uncomment this
    // use SoftDeletes;

    protected $table = 'vendors';

    protected $fillable = [
        'name',
        'code',
        'type',
        'phone',
        'email',
        'address',
        'google_maps_link',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'npwp_number',
        'vendor_photo',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => 'string',
        'status' => 'string',
    ];

    /**
     * Relationships
     */

    // If vendors are linked to units
    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    // If vendors are linked to goods (optional)
    public function driver()
    {
        return $this->hasMany(Driver::class);
    }

    // Audit trail
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
