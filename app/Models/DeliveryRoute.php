<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeliveryRoute extends Model
{
    use HasFactory;

    protected $table = 'delivery_routes';

    protected $fillable = [
        'delivery_id',
        'origin_id',
        'destination_id',
        'route_id',
        'use_multi_origin',
        'use_multi_destination',
        'use_multi_origin_2',
        'use_multi_destination_2',
        'multi_origin_1',
        'multi_origin_2',
        'multi_dest_1',
        'multi_dest_2',
        'amount_multi_origin_1',
        'amount_multi_origin_2',
        'amount_multi_dest_1',
        'amount_multi_dest_2',
        'discount_tuslah',
        'amount_dt',
        'amount',
        'unit_type',
        'delivery_type',
        'target_arrival_date',
        'target_load_date',
        'target_unload_date',
        'target_load_complete_date',
        'target_unload_complete_date',
        'load_notes',
        'unload_notes',
        'sla',
        'quantity_kilograms',
        'created_by',
        'updated_by',
    ];

    /**
     * Relationships
     */

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function origin()
    {
        return $this->belongsTo(Origin::class, 'origin_id');
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class, 'destination_id');
    }

    public function deliveryType()
    {
        return $this->belongsTo(CustomerDeliveryType::class, 'delivery_type');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function multiOrigin1()
    {
        return $this->belongsTo(City::class, 'multi_origin_1');
    }

    public function multiDest1()
    {
        return $this->belongsTo(City::class, 'multi_dest_1');
    }

    public function multiDest2()
    {
        return $this->belongsTo(City::class, 'multi_dest_2');
    }

    public function multiOrigin2()
    {
        return $this->belongsTo(City::class, 'multi_origin_2');
    }

    public function voucherUssage()
    {
        return $this->belongsTo(RoutesVoucherUssage::class, 'route_id');
    }
}
