<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitAgreementHistory extends Model
{
    use HasFactory;

    protected $table = 'unit_agreement_history';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'unit_id',
        'agreement_status',
        'customer_id',
        'docs_id',
        'remarks',
        'created_date',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Relation: has one document upload
     */
    public function document()
    {
        return $this->hasOne(AccountsDocUpload::class, 'id', 'docs_id');
    }
}
