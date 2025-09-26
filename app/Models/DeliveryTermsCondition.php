<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryTermsCondition extends Model
{
    use HasFactory;

    protected $table = 'delivery_terms_conditions';

    protected $fillable = [
        'delivery_id',
        'tnc_name',
        'tnc_description',
        'created_by',
    ];

    public $timestamps = false; // since you're using created_date manually

    protected $dates = [
        'created_date',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }
}
