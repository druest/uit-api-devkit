<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutesVoucherUssage extends Model
{
    protected $table = 'routes_voucher_ussage';
    protected $fillable = [
        'voucher_id',
        'category',
        'reference_id',
        'reference_type',
        'amount',
        'description',
        'effective_date',
        'created_by',
        'created_at',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(RouteVoucher::class, 'voucher_id');
    }

    // public function destination(): BelongsTo
    // {
    //     return $this->belongsTo(City::class, 'destination_city');
    // }

    // public function creator(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'created_by');
    // }

    // public function updater(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'updated_by');
    // }
}
