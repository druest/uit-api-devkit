<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'created_by',
        'updated_by',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function trfExpenses()
    {
        return $this->hasMany(TrfExpense::class);
    }

    public function trfExpenseOthers()
    {
        return $this->hasMany(TrfExpenseOther::class);
    }
}
