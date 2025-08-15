<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DestinationExpense;
use App\Models\ExpenseType;
use App\Models\TrfExpenseOther;
use App\Models\Vendor;
use App\Models\WorkOrder;
use App\Models\WorkOrderExpense;
use App\Models\WorkOrderOtherExpense;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExpenseController extends BaseController
{
    public function getOutstandingWOPayment(): LengthAwarePaginator
    {
        $expense = WorkOrder::query()
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->with(['expenses:id,work_order_id,amount'])
            ->orderByAsc('created_at')
            ->paginate(20);

        $expenseOther = WorkOrderOtherExpense::query()
            ->where('status', 'pending')
            ->with(['workOrder:unit,driver', 'expenseType'])
            ->orderByAsc('created_at')
            ->paginate(20);

        return $this->sendResponse([$expense => $expense, $expenseOther => $expenseOther], "Success");
    }

    public function submitExpense()
    {
    }

    public function submitOtherExpense(Request $request)
    {
        $validated = $request->validate([
            'work_order_other_expense_id' => 'required|exists:work_order_other_expenses,id',
            'company_account_id' => 'required|exists:company_accounts,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $userId = auth()->id();

        return DB::transaction(function () use ($validated, $userId) {
            $expense = TrfExpenseOther::create([
                'work_order_other_expense_id' => $validated['work_order_other_expense_id'],
                'company_account_id' => $validated['company_account_id'],
                'amount' => $validated['amount'],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            WorkOrderOtherExpense::where('id', $validated['work_order_other_expense_id'])
                ->update([
                    'status' => 'completed',
                    'updated_by' => $userId,
                ]);

            return response()->json([
                'message' => 'Expense recorded and status updated successfully.',
                'data' => $expense,
            ], 201);
        });
    }
}
