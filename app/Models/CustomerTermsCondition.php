<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerTermsCondition extends Model
{
    use HasFactory;

    protected $table = 'customer_terms_conditions';

    protected $fillable = [
        'customer_id',
        'tnc_name',
        'tnc_description',
        'effective_date',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'is_active' => 'boolean',
    ];

    public $timestamps = false; // since you're using created_date manually

    protected $dates = [
        'created_date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
