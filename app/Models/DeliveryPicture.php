<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryPicture extends Model
{
    protected $table = 'delivery_picture';

    public $timestamps = false; // because you're using created_date manually

    protected $fillable = [
        'type',
        'ref_id',
        'file_name',
        'file_path',
        'created_date',
        'created_by',
    ];

    // Optional: define relationship if created_by refers to users
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
