<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrfExpenseOther extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_other_expense_id',
        'company_account_id',
        'amount',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // 🔗 Relationships
    public function workOrderOtherExpense()
    {
        return $this->belongsTo(WorkOrderOtherExpense::class);
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
