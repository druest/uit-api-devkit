<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountsDocUpload extends Model
{
    use HasFactory;

    protected $table = 'accounts_doc_upload';
    public $timestamps = false;
    protected $fillable = [
        'ref_type',
        'ref_id',
        'name',
        'category',
        'doc_number',
        'effective_date',
        'expiry_date',
        'remarks',
        'file_path',
        'created_date',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'created_date' => 'datetime',
    ];

    // Optional: relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference()
    {
        return $this->morphTo(null, 'ref_type', 'ref_id');
    }
}
