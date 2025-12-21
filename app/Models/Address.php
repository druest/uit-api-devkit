<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Address extends Model
{
    use HasFactory;

    protected $table = 'addresses';

    protected $fillable = [
        'ref_type',
        'ref_id',
        'category',
        'address',
        'city',
        'province',
        'postal_code',
        'phone',
        'email',
    ];

    protected $casts = [
        'ref_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Polymorphic relationship to the referenced model.
     */
    public function reference()
    {
        return $this->morphTo(null, 'ref_type', 'ref_id');
    }
}
