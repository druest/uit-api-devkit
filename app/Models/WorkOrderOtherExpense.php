<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkOrderOtherExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'expense_type_id',
        'amount',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'notes',
        'is_billed_to_customer',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_billed_to_customer' => 'boolean',
        'amount' => 'decimal:2',
    ];

    // 🔗 Relationships
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class);
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
