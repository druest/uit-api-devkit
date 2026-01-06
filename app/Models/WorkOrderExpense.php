<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderExpense extends Model
{
    protected $fillable = [
        'work_order_id',
        'amount',
        'dr_bank_name',
        'dr_account_number',
        'dr_account_name',
        'company_account_id',
        'created_by',
        'updated_by',
    ];

    // Relationships
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function destinationExpense(): BelongsTo
    {
        return $this->belongsTo(DestinationExpense::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
