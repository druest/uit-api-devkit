<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Driver extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'nik',
        'education',
        'marriage_status',
        'email',
        'religion',
        'joined_date',
        'birth_date',
        'address',
        'phone',
        'photo',
        'ownership',
        'vendor_id',
        'ktp_photo',
        'sim_photo',
        'house_photo',
        'google_maps_link',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'status',
        'emergency_contact',
        'emergency_name',
        'emergency_relation',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'status' => 'string',
        'ownership' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors
    public function getOwnershipLabelAttribute()
    {
        return ucfirst($this->ownership);
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst($this->status);
    }

    public function getFullBankInfoAttribute()
    {
        return "{$this->bank_name} - {$this->bank_account_number} ({$this->bank_account_name})";
    }

    public function driverGroupMappings()
    {
        return $this->hasMany(DriverGroupMapping::class, 'driver_id');
    }
}
