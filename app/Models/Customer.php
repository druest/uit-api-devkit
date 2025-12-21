<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'service_category',
        'customer_id',
        'code',
        'email',
        'tax_id_number',
        'address',
        'payment_due_date',
        'description',
        'is_taxable',
        'requires_pph23',
        'requires_final_tax',
        'register_date',
        'parent_customer',
        'is_delivery_customer',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'parent_customer');
    }

    public function termsconditions(): HasMany
    {
        return $this->hasMany(CustomerTermsCondition::class, 'customer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function expenseParam(): HasOne
    {
        return $this->hasOne(CustomerExpenseParam::class);
    }

    public function voucherUsages()
    {
        return $this->hasMany(RoutesVoucherUssage::class, 'reference_id')
            ->where('category', 'customer');
    }
}
