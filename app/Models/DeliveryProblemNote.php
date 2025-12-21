<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryProblemNote extends Model
{
    use HasFactory;

    // Nama tabel
    protected $table = 'delivery_problem_notes';

    // Primary key
    protected $primaryKey = 'id';

    // Kolom yang bisa diisi (mass assignment)
    protected $fillable = [
        'delivery_id',
        'type',
        'problem_details',
        'customer_notes',
        'vendor_notes',
        'company_notes',
        'created_by',
        'created_date',
    ];

    // Kalau created_date pakai timestamp custom
    public $timestamps = false;

    // Relasi ke tabel deliveries
    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'delivery_id');
    }

    public function supportingDocs(): HasMany
    {
        return $this->hasMany(DeliveryProblemSupportingDoc::class, 'delivery_problem_id');
    }

    // Helper: ambil semua notes by delivery
    public static function forDelivery($deliveryId)
    {
        return self::where('delivery_id', $deliveryId)->orderBy('created_date', 'desc')->get();
    }
}
