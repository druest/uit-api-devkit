<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactPic extends Model
{
    use HasFactory;

    protected $table = 'contacts_pic';

    protected $fillable = [
        'ref_type',
        'ref_id',
        'name',
        'role',
        'phone',
        'email',
        'remarks',
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
