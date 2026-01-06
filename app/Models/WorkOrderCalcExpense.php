<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkOrderCalcExpense extends Model
{
    protected $table = 'work_order_calc_expenses';

    public $timestamps = false;

    protected $fillable = [
        'work_order_id',
        'expense_type_id',
        'amount',
        'amount_multi_origin_1',
        'amount_multi_origin_2',
        'amount_multi_dest_1',
        'amount_multi_dest_2',
        'note',
        'created_by',
    ];

    // Relationships
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
