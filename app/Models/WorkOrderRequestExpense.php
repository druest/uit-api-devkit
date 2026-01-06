<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderRequestExpense extends Model
{
    use HasFactory;

    protected $table = 'work_order_request_expenses';
    public $timestamps = false;

    protected $fillable = [
        'work_order_request_id',
        'expense_type',
        'amount',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function request()
    {
        return $this->belongsTo(WorkOrderRequest::class, 'work_order_request_id');
    }

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type');
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
