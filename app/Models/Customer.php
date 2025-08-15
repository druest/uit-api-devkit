<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'email',
        'tax_id_number',
        'address',
        'payment_due_date',
        'description',
        'is_taxable',
        'requires_final_tax',
        'register_date',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
        'requires_final_tax' => 'boolean',
        'is_active' => 'boolean',
        'payment_due_date' => 'date',
        'register_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
