<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RouteVoucher extends Model
{
    protected $table = 'routes_vouchers';
    protected $fillable = [
        'voucher_code',
        'origin_city',
        'destination_city',
        'duration_value',
        'duration_display',
        'distance_value',
        'distance_display',
        'description',
        'created_by',
        'created_at',
    ];

    public function origin(): BelongsTo
    {
        return $this->belongsTo(City::class, 'origin_city');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(City::class, 'destination_city');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function voucherUssage(): HasMany
    {
        return $this->hasMany(RoutesVoucherUssage::class, 'voucher_id');
    }
}
