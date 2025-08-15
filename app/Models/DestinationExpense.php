<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DestinationExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'destination_id',
        'expense_type_id',
        'trip_mode',
        'amount',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function destination()
    {
        return $this->belongsTo(Destination::class);
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

    // Optional: Enum accessor
    public function getTripModeLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->trip_mode));
    }
}
