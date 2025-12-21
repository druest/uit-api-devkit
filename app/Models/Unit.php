<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'plate_full',
        'plate_region',
        'plate_number',
        'plate_suffix',
        'ownership',
        'vendor_id',
        'brand',
        'manufactured_year',
        'frame_number',
        'engine_number',
        'type',
        'do_type',
        'wheels',
        'capacity',
        'color',
        'is_active',
        'length',
        'width',
        'height',
        'tax_due_date',
        'keur_expiry_date',
        'stnk_expiry_date',
        'bpkb_file',
        'stnk_file',
        'insurance_expiry_date',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'manufactured_year' => 'integer',
        'wheels' => 'integer',
        'length' => 'float',
        'width' => 'float',
        'height' => 'float',
        'tax_due_date' => 'date',
        'keur_expiry_date' => 'date',
        'stnk_expiry_date' => 'date',
        'insurance_expiry_date' => 'date',
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
    public function getPlateLabelAttribute()
    {
        return "{$this->plate_region} {$this->plate_number}{$this->plate_suffix}";
    }

    public function getOwnershipLabelAttribute()
    {
        return ucfirst($this->ownership);
    }

    public function getDimensionsAttribute()
    {
        return "{$this->length}m x {$this->width}m x {$this->height}m";
    }

    public function unitGroupMappings()
    {
        return $this->hasMany(UnitGroupMapping::class, 'unit_id');
    }
}
