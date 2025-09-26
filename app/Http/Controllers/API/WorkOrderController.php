<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DestinationExpense;
use App\Models\ExpenseType;
use App\Models\Vendor;
use App\Models\WorkOrder;
use App\Models\WorkOrderExpense;
use App\Models\WorkOrderOtherExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkOrderController extends BaseController
{
    // public function index(Request $request)
    // {
    //     $query = WorkOrder::query();

    //     if ($request->filled('status')) {
    //         $query->where('work_order_status', $request->status);
    //     }

    //     if ($request->filled('driver_id')) {
    //         $query->where('driver_id', $request->driver_id);
    //     }

    //     if ($request->filled('date_from') && $request->filled('date_to')) {
    //         $query->whereBetween('assigned_at', [$request->date_from, $request->date_to]);
    //     }

    //     return response()->json($query->latest()->paginate(20));
    // }
    public function canCreateDeliveryWO($id)
    {
        $getWO = WorkOrder::where('delivery_id', $id)->get();
    }

    private function checkDeliveryWO($id)
    {
        $getWO = WorkOrder::where('delivery_id', $id)->get();
        return $getWO ? true : false;
    }

    public function index()
    {
        return WorkOrder::with(['status', 'type', 'unit', 'driver', 'expenses', 'creator', 'updater'])->paginate(20);
    }

    public function getByStatus($id)
    {
        if ($id == 0) {
            return WorkOrder::with(['status', 'type', 'unit', 'driver', 'expenses', 'creator', 'updater'])->paginate(20);
        }
        return WorkOrder::where('work_order_status', $id)->with(['status', 'type', 'unit', 'driver', 'expenses', 'creator', 'updater'])->paginate(20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'work_order_type_id' => 'required|integer',
            'unit_id' => 'required|integer',
            'driver_id' => 'required|integer',
            'work_order_status' => 'required|exists:work_order_statuses,id',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'bank_account_name' => 'nullable|string',
            'notes' => 'nullable|string',
            'vendor_id' => 'required|integer',
            'delivery_id' => 'required|integer',
            'sla' => 'required|integer',
        ]);

        $validatedExpense = $request->validate([
            'selected_expense' => 'required|array',
            'selected_expense.*.destination_expense_id' => 'required|integer',
            'selected_expense.*.amount' => 'required|numeric',
        ]);

        $workOrder = DB::transaction(function () use ($validated, $validatedExpense) {
            $validated['payment_status'] = 'unpaid';
            $validated['created_by'] = auth()->id();
            $validated['assigned_at'] = now();
            $validated['code'] = $this->generateWorkOderCode(
                $validated['vendor_id'],
                $validated['delivery_id'],
                $validated['work_order_type_id']
            );

            $workOrder = WorkOrder::create($validated);

            $cleanedExpense = collect($validatedExpense['selected_expense'])->map(function ($expense) use ($workOrder) {
                return [
                    'work_order_id' => $workOrder->id,
                    'destination_expense_id' => $expense['destination_expense_id'],
                    'amount' => $expense['amount'],
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ];
            })->toArray();

            WorkOrderExpense::insert($cleanedExpense);

            Delivery::findOrFail($validated['delivery_id'])->update([
                'status_id' => 2,
                'updated_by' => auth()->id(),
            ]);

            return $workOrder;
        });

        return response()->json($workOrder, 201);
    }

    public function show($id)
    {
        return WorkOrder::with([
            'type', 'expenses.destinationExpense.expenseType', 'otherExpenses.expenseType', 'workOrderEvents', 'workOrderEvents.deliveryPhase', 'workOrderEvents.createdBy',
            'unit.vendor', 'driver', 'status', 'creator', 'updater'
        ])->findOrFail($id);
    }

    public function update(Request $request, WorkOrder $workOrder)
    {
        $validated = $request->validate([
            'work_order_type_id' => 'sometimes|integer',
            'unit_id' => 'sometimes|integer',
            'driver_id' => 'sometimes|integer',
            'work_order_status' => 'sometimes|exists:work_order_statuses,id',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'bank_account_name' => 'nullable|string',
            'notes' => 'nullable|string',
            'assigned_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
        ]);

        $validated['updated_by'] = auth()->id();

        $workOrder->update($validated);

        return response()->json($workOrder);
    }

    public function destroy(WorkOrder $workOrder)
    {
        $workOrder->delete();

        return response()->json(['message' => 'Work order deleted']);
    }

    public function getTripPackageByDestinationID($id)
    {
        $expenses = DestinationExpense::with(['destination', 'expenseType'])->where('destination_id', $id)->get();
        $summary = $expenses->groupBy('trip_mode')
            ->sortKeys()
            ->map(function ($group, $mode) {
                return [
                    'name' => ucwords($mode),
                    'expense_detail' => $group->values(), // reset keys
                    'mode' => $mode,
                    'total_amount' => $group->sum('amount'),
                    'count' => $group->count(),
                ];
            })->values();
        return $this->sendResponse($summary, "Success");
    }

    private function generateWorkOderCode($vendorID, $deliveryID, $type)
    {
        $vendor = Vendor::with(['creator', 'updater'])->findOrFail($vendorID);

        $prefix = 'UWO';
        $timestamp = now()->format('Ymd');
        $vendorCode = $vendor->code;

        if ($type == 1) {
            $countDelivery = WorkOrder::where('delivery_id', $deliveryID)->count();
            $sequence = $countDelivery + 1;
            $sequencePart = str_pad($sequence, 3, '0', STR_PAD_LEFT);
            $sequenceDeliveryPart = str_pad($deliveryID, 3, '0', STR_PAD_LEFT);
            return "{$prefix}-{$vendorCode}-{$sequenceDeliveryPart}-{$timestamp}-{$sequencePart}";
        } else {
            $today = now()->toDateString();
            $countToday = WorkOrder::whereDate('assigned_at', $today)->count();
            $sequence = $countToday + 1;
            $sequencePart = str_pad($sequence, 3, '0', STR_PAD_LEFT);
            return "{$prefix}-{$vendorCode}-{$timestamp}-{$sequencePart}";
        }
    }

    public function getOutstandingOtherExpense($id)
    {
        return WorkOrderOtherExpense::query()
            ->where('status', 'pending')
            ->where('work_order_id', $id)
            ->with(['expenseType', 'creator', 'updater'])
            ->paginate(20);
    }

    public function getListOtherExpense()
    {
        return ExpenseType::query()->paginate(20);
    }

    public function storeWorkOrderOtherExpense(Request $request)
    {
        $validated = $request->validate([
            'work_order_id' => 'required|exists:work_orders,id',
            'expense_type_id' => 'required|exists:expense_types,id',
            'amount' => 'required|numeric|min:0',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_billed_to_customer' => 'boolean',
        ]);

        WorkOrderOtherExpense::create([
            ...$validated,
            'created_by' => auth()->id(),
            'status' => 'pending',
        ]);

        $paginated = WorkOrderOtherExpense::query()
            ->where('work_order_id', $validated['work_order_id'])
            ->with(['expenseType', 'creator', 'updater'])
            ->paginate(20);

        return $paginated->total() === 1
            ? $paginated->items()
            : $paginated;
    }
}
