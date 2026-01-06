<?php

namespace App\Http\Controllers\API;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\CompanyAccount;
use App\Models\Delivery;
use App\Models\DestinationExpense;
use App\Models\ExpenseType;
use App\Models\TrfExpense;
use App\Models\TrfExpenseOther;
use App\Models\Vendor;
use App\Models\WorkOrder;
use App\Models\WorkOrderExpense;
use App\Models\WorkOrderOtherExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends BaseController
{
    public function getOutstandingWOPayment()
    {
        $expenses = WorkOrder::query()
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('trf_available', 1)
            ->with([
                'delivery.customer',
                'delivery.routes.multiDest1',
                'delivery.routes.multiDest2',
                'delivery.routes.multiOrigin1',
                'delivery.routes.multiOrigin2',
                'delivery.routes.voucherUssage.voucher.origin',
                'delivery.routes.voucherUssage.voucher.destination',
                'unit',
                'driver',
                'expenses:id,work_order_id,amount',
                'woCal'
            ])
            ->orderBy('created_at', 'asc')
            ->get(); // <-- execute query

        return $this->sendResponse($expenses, "Success");
    }

    public function generateInvoice()
    {
        $data = [
            'driver_name'   => 'Dede Ahmad Faturachman',
            'driver_number' => '5495068622',
            'date'          => '24 December 2025 06:00',
            'uj_number'     => 'UJ-251224-001',
            'description'   => 'Uang Jalan OPL rute CIB99A - CRN99A - MDN99A - JOG99A - TSK99A',
            'terbilang'     => 'Dua Juta Sembilan Ratus Tujuh Ribu Tujuh Ratus Sembilan Puluh Enam',
            'amount'        => 2907796,
        ];

        $html = view('expense.template', $data)->render();
        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'portrait');

        return $pdf->stream('expense.pdf');
    }

    public function submitExpense(Request $request)
    {
        $validated = $request->validate([
            'work_order_id' => 'required|exists:work_orders,id',
            'company_account_id' => 'required|exists:company_accounts,id',
            'amount' => 'required|numeric|min:0',
            'dr_bank_name' => 'required|string',
            'dr_account_number' => 'required|string',
            'dr_account_name' => 'required|string',
            'transaction_date' => 'required|date',
        ]);

        $userId = auth()->id();

        return DB::transaction(function () use ($validated, $userId) {
            $expense = TrfExpense::create([
                'work_order_id' => $validated['work_order_id'],
                'company_account_id' => $validated['company_account_id'],
                'amount' => $validated['amount'],
                'dr_bank_name' => $validated['dr_bank_name'],
                'dr_account_number' => $validated['dr_account_number'],
                'dr_account_name' => $validated['dr_account_name'],
                'amount_in_words' => $this->for_terbilang($validated['amount']),
                'transaction_date' => $validated['transaction_date'],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            WorkOrder::where('id', $validated['work_order_id'])
                ->update([
                    'work_order_status' => 2,
                    'payment_status' => 'paid',
                    'updated_by' => $userId,
                ]);

            $workOrder = WorkOrder::findOrFail($validated['work_order_id']);
            $workOrder->delivery()->update(['status_id' => 3, 'updated_by' => auth()->id(),]);

            return response()->json([
                'message' => 'Expense recorded and status updated successfully.',
                'data' => $expense,
            ], 201);
        });
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

    public function paymentDeliveryExpense(Request $request)
    {
        $validated = $request->validate([
            'work_order_other_expense_id' => 'required|exists:work_order_other_expenses,id',
            'company_account_id' => 'required|exists:company_accounts,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $userId = auth()->id();
    }

    public function getCompanyAccounts()
    {
        return CompanyAccount::all();
    }

    private function penyebut($nilai)
    {
        $nilai = abs($nilai);
        $huruf = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];

        if ($nilai < 12) {
            return " " . $huruf[$nilai];
        }
        if ($nilai < 20) {
            return $this->penyebut($nilai - 10) . " belas";
        }
        if ($nilai < 100) {
            return $this->penyebut(intdiv($nilai, 10)) . " puluh" . $this->penyebut($nilai % 10);
        }
        if ($nilai < 200) {
            return " seratus" . $this->penyebut($nilai - 100);
        }
        if ($nilai < 1000) {
            return $this->penyebut(intdiv($nilai, 100)) . " ratus" . $this->penyebut($nilai % 100);
        }
        if ($nilai < 2000) {
            return " seribu" . $this->penyebut($nilai - 1000);
        }
        if ($nilai < 1000000) {
            return $this->penyebut(intdiv($nilai, 1000)) . " ribu" . $this->penyebut($nilai % 1000);
        }
        if ($nilai < 1000000000) {
            return $this->penyebut(intdiv($nilai, 1000000)) . " juta" . $this->penyebut($nilai % 1000000);
        }
        if ($nilai < 1000000000000) {
            return $this->penyebut(intdiv($nilai, 1000000000)) . " milyar" . $this->penyebut($nilai % 1000000000);
        }
        if ($nilai < 1000000000000000) {
            return $this->penyebut(intdiv($nilai, 1000000000000)) . " trilyun" . $this->penyebut($nilai % 1000000000000);
        }
        return "";
    }

    private function for_terbilang($nilai)
    {
        $hasil = $nilai < 0 ? "minus " . trim($this->penyebut($nilai)) : trim($this->penyebut($nilai));
        return ucwords($hasil) . " Rupiah";
    }
}
