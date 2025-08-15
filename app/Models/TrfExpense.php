<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrfExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'company_account_id',
        'amount',
        'created_by',
        'updated_by',
    ];

    // Relationships
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function companyAccount()
    {
        return $this->belongsTo(CompanyAccount::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
