<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryPicture;
use App\Models\DeliveryProblemNote;
use App\Models\DeliveryProblemSupportingDoc;
use App\Models\DeliveryRoute;
use App\Models\DestinationExpense;
use App\Models\ExpenseType;
use App\Models\Vendor;
use App\Models\WorkOrder;
use App\Models\WorkOrderEvent;
use App\Models\WorkOrderExpense;
use App\Models\WorkOrderOtherExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\WorkOrderCalcExpense;
use App\Models\VendorWorkOrder;
use Illuminate\Support\Facades\Storage;

class WorkOrderController extends BaseController
{
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

    public function store_bak(Request $request)
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

        $validatedOtherExpense = $request->validate([
            'other_expense' => 'nullable|array',
            'other_expense.*.expense_type_id' => 'required|exists:expense_types,id',
            'other_expense.*.amount' => 'required|numeric|min:0',
            'other_expense.*.bank_name' => 'nullable|string|max:255',
            'other_expense.*.bank_account_number' => 'nullable|string|max:255',
            'other_expense.*.bank_account_name' => 'nullable|string|max:255',
            'other_expense.*.notes' => 'nullable|string',
            'other_expense.*.is_billed_to_customer' => 'boolean',
        ]);

        $workOrder = DB::transaction(function () use ($validated, $validatedExpense, $validatedOtherExpense) {
            $validated['payment_status'] = 'unpaid';
            $validated['created_by'] = auth()->id();
            $validated['assigned_at'] = now();
            $validated['code'] = $this->generateWorkOderCode(
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

            $cleanedOtherExpense = collect($validatedOtherExpense['other_expense'])->map(function ($expense) use ($workOrder) {
                return [
                    'work_order_id' => $workOrder->id,
                    'expense_type_id' => $expense['expense_type_id'],
                    'amount' => $expense['amount'],
                    'bank_name' => $expense['bank_name'],
                    'bank_account_number' => $expense['bank_account_number'],
                    'bank_account_name' => $expense['bank_account_name'],
                    'notes' => $expense['notes'],
                    'is_billed_to_customer' => $expense['is_billed_to_customer'],
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ];
            })->toArray();

            $getDeliveryRoutes = DeliveryRoute::where('delivery_id', $validated['delivery_id'])->first();

            $eventData = [
                ['work_order_id' => $workOrder->id, 'delivery_phase_id' => 2, 'target_time' => $getDeliveryRoutes->target_load_date, 'status' => 'not started'],
                ['work_order_id' => $workOrder->id, 'delivery_phase_id' => 4, 'target_time' => $getDeliveryRoutes->target_load_complete_date, 'status' => 'not started'],
                ['work_order_id' => $workOrder->id, 'delivery_phase_id' => 6, 'target_time' => $getDeliveryRoutes->target_unload_date, 'status' => 'not started'],
                ['work_order_id' => $workOrder->id, 'delivery_phase_id' => 8, 'target_time' => $getDeliveryRoutes->target_unload_complete_date, 'status' => 'not started']
            ];

            $SLAeventData = [
                ['work_order_id' => $workOrder->id, 'delivery_phase_id' => 5, 'target_duration_in_seconds' => $getDeliveryRoutes->sla, 'status' => 'not started'],
            ];

            WorkOrderExpense::insert($cleanedExpense);

            WorkOrderOtherExpense::insert($cleanedOtherExpense);

            WorkOrderEvent::insert($eventData);

            WorkOrderEvent::insert($SLAeventData);

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
            'type',
            'expenses.destinationExpense.expenseType',
            'otherExpenses.expenseType',
            'workOrderEvents' => function ($query) {
                $query->orderBy('delivery_phase_id');
            },
            'workOrderEvents.deliveryPhase',
            'workOrderEvents.createdBy',
            'vendorwo',
            'unit.vendor',
            'driver',
            'second_driver',
            'status',
            'creator',
            'updater'
        ])->findOrFail($id);
    }

    public function getCalculatedeExpense($id)
    {
        return WorkOrderCalcExpense::where('work_order_id', $id)->firstOrFail();
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

    private function generateWorkOderCode($deliveryID, $type)
    {
        //$vendor = Vendor::with(['creator', 'updater'])->findOrFail($vendorID);

        $prefix = 'UWO';
        $timestamp = now()->format('Ymd');
        $vendorCode = '';

        if ($type == 1) {
            $countDelivery = WorkOrder::where('delivery_id', $deliveryID)->count();
            $sequence = $countDelivery + 1;
            $sequencePart = str_pad($sequence, 3, '0', STR_PAD_LEFT);
            $sequenceDeliveryPart = str_pad($deliveryID, 3, '0', STR_PAD_LEFT);
            return "{$prefix}-{$sequenceDeliveryPart}-{$timestamp}-{$sequencePart}";
        } else {
            $today = now()->toDateString();
            $countToday = WorkOrder::whereDate('assigned_at', $today)->count();
            $sequence = $countToday + 1;
            $sequencePart = str_pad($sequence, 3, '0', STR_PAD_LEFT);
            return "{$prefix}-{$timestamp}-{$sequencePart}";
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

    public function storeUpdatedExternalWO(Request $request)
    {

        $route = WorkOrder::findOrFail($request->work_order_id);

        $route->update([
            'driver_id' => $request->driver_id,
            'secondary_driver_id' => $request->secondary_driver_id,
        ]);

        return response()->json([
            'message' => 'Driver updated successfully.',
            'data' => $route,
        ]);
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

    public function dcsView()
    {
        return Delivery::with([
            'status',
            'workOrders',
            'workOrders.unit',
            'workOrders.driver',
            'workOrders.second_driver',
            'workOrders.workOrderEvents',
            'workOrders.old_driver',
            'workOrders.old_second_driver',
            'routes.destination',
            'routes.deliveryType',
            'routes.voucherUssage.voucher.origin', 'routes.voucherUssage.voucher.destination',
            'deliveryPlan',
            'routes.secondaryOrigin.city', 'routes.multiDest1.city', 'routes.multiDest2.city', 'routes.multiDest3.city',
            'deliveryProblem' => function ($q) {
                $q->where('type', 'dest')
                    ->with('supportingDocs');
            },
            'deliveryPlan.unit',
            'deliveryPlan.vendor',
            'routes.destination.city',
            'routes.destination.origin.city',
            'customer',
            'creator',
            'updater'
        ])
            ->has('workOrders')
            ->paginate(20);
    }

    public function scsView()
    {
        return Delivery::with([
            'status',
            'workOrders',
            'workOrders.unit',
            'workOrders.driver',
            'workOrders.workOrderEvents',
            'workOrders.second_driver',
            'workOrders.old_driver',
            'workOrders.old_second_driver',
            'routes.destination',
            'routes.deliveryType',
            'routes.voucherUssage.voucher.origin', 'routes.voucherUssage.voucher.destination',
            'deliveryPlan',
            'routes.secondaryOrigin.city', 'routes.multiDest1.city', 'routes.multiDest2.city', 'routes.multiDest3.city',
            'deliveryProblem' => function ($q) {
                $q->where('type', 'site')
                    ->with('supportingDocs');
            },
            'deliveryPlan.unit',
            'deliveryPlan.vendor',
            'routes.destination.city',
            'routes.destination.origin.city',
            'customer',
            'creator',
            'updater'
        ])
            ->has('workOrders')
            ->paginate(20);
    }

    public function reconcile()
    {
        $deliveries = Delivery::with([
            'status',
            'workOrders',
            'workOrders.unit',
            'workOrders.driver',
            'workOrders.workOrderEvents',
            'workOrders.second_driver',
            'workOrders.old_driver',
            'workOrders.old_second_driver',
            'routes.destination',
            'routes.deliveryType',
            'deliveryPlan',
            'routes.secondaryOrigin.city',
            'routes.multiDest1.city',
            'routes.multiDest2.city',
            'routes.multiDest3.city',
            'routes.voucherUssage.voucher.origin', 'routes.voucherUssage.voucher.destination',
            'deliveryProblem.supportingDocs', // load semua dulu
            'deliveryPlan.unit',
            'deliveryPlan.vendor',
            'routes.destination.city',
            'routes.destination.origin.city',
            'customer',
            'creator',
            'updater'
        ])
            ->has('workOrders')
            ->paginate(20);

        // transform hasilnya
        $deliveries->getCollection()->transform(function ($delivery) {
            $delivery->delivery_problem_site  = $delivery->deliveryProblem->where('type', 'site')->values();
            $delivery->delivery_problem_dest  = $delivery->deliveryProblem->where('type', 'dest')->values();
            $delivery->delivery_problem_fleet = $delivery->deliveryProblem->where('type', 'fleet')->values();

            // kalau mau, bisa unset property asli biar nggak double
            unset($delivery->deliveryProblem);

            $pictures = DeliveryPicture::where('ref_id', $delivery->id)->get();

            $pictures = $pictures->map(function ($pic) {
                $pic->url = asset(ltrim($pic->file_path, '/'));
                return $pic;
            });

            $delivery->deliveryPictures = $pictures;

            return $delivery;
        });

        return $deliveries;
    }

    public function fleetMonitoringView()
    {
        $deliveries = Delivery::with([
            'status',
            'workOrders',
            'workOrders.unit',
            'workOrders.checkpoints',
            'workOrders.driver',
            'workOrders.workOrderEvents',
            'workOrders.second_driver',
            'workOrders.old_driver',
            'workOrders.old_second_driver',
            'workOrders.old_unit',
            'routes.destination',
            'routes.deliveryType',
            'routes.voucherUssage.voucher.origin', 'routes.voucherUssage.voucher.destination',
            'routes.secondaryOrigin.city', 'routes.multiDest1.city', 'routes.multiDest2.city', 'routes.multiDest3.city',
            'deliveryProblem' => function ($q) {
                $q->where('type', 'fleet')
                    ->with('supportingDocs');
            },
            'deliveryPlan',
            'deliveryPlan.unit',
            'deliveryPlan.vendor',
            'routes.destination.city',
            'routes.destination.origin.city',
            'customer',
            'creator',
            'updater'
        ])->has('workOrders')->paginate(20);

        // Manually append deliveryPictures 
        foreach ($deliveries as $delivery) {
            $pictures = DeliveryPicture::where('ref_id', $delivery->id)->get();

            // Add URL to each picture
            $pictures = $pictures->map(function ($pic) {
                $pic->url = asset(ltrim($pic->file_path, '/'));
                return $pic;
            });

            $delivery->deliveryPictures = $pictures;
        }

        return response()->json($deliveries);
    }

    public function store(Request $request)
    {
        $data = $request->calculatedata;

        $validated = $request->validate([
            'work_order_type_id' => 'required|integer',
            'unit_id' => 'required|integer',
            'uj_type' => 'required|string',
            'driver_id' => 'required|integer',
            'secondary_driver_id' => 'nullable|integer',
            'work_order_status' => 'required|exists:work_order_statuses,id',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'bank_account_name' => 'nullable|string',
            'delivery_id' => 'required|integer',
            'di_notes' => 'nullable|string'
        ]);

        $workOrder = DB::transaction(function () use ($validated, $data) {
            $validated['payment_status'] = 'unpaid';
            $validated['created_by'] = auth()->id();
            $validated['assigned_at'] = now();
            $validated['code'] = $this->generateWorkOderCode(
                $validated['delivery_id'],
                $validated['work_order_type_id']
            );

            $workOrder = WorkOrder::create($validated);

            $total = ($data['driver_fee'] ?? 0)
                + ($data['secondary_driver_fee'] ?? 0)
                + ($data['delivery_fuel_price'] ?? 0)
                + ($data['return_fuel_price'] ?? 0)
                + ($data['load_fee'] ?? 0)
                + ($data['unload_fee'] ?? 0)
                + ($data['additional_fee'] ?? 0);

            WorkOrderCalcExpense::create([
                'work_order_id'        => $workOrder->id,
                'driver_fee'           => $data['driver_fee'] ?? 0,
                'secondary_driver_fee' => $data['secondary_driver_fee'] ?? 0,
                'delivery_fuel_price'  => $data['delivery_fuel_price'] ?? 0,
                'return_fuel_price'    => $data['return_fuel_price'] ?? 0,
                'load_fee'             => $data['load_fee'] ?? 0,
                'unload_fee'           => $data['unload_fee'] ?? 0,
                'additional_fee'       => $data['additional_fee'] ?? 0,
                'note'                 => $data['note'] ?? null,
                'total'                => $total,
                'created_by'           => auth()->id(),
            ]);

            $getDeliveryRoutes = DeliveryRoute::where('delivery_id', $validated['delivery_id'])->first();

            // $eventData = [
            //     ['work_order_id' => $workOrder->id, 'delivery_phase_id' => 2, 'target_time' => $getDeliveryRoutes->target_load_date, 'status' => 'not started'],
            //     ['work_order_id' => $workOrder->id, 'delivery_phase_id' => 4, 'target_time' => $getDeliveryRoutes->target_load_complete_date, 'status' => 'not started'],
            //     ['work_order_id' => $workOrder->id, 'delivery_phase_id' => 6, 'target_time' => $getDeliveryRoutes->target_unload_date, 'status' => 'not started'],
            //     ['work_order_id' => $workOrder->id, 'delivery_phase_id' => 8, 'target_time' => $getDeliveryRoutes->target_unload_complete_date, 'status' => 'not started']
            // ];

            // $SLAeventData = [
            //     ['work_order_id' => $workOrder->id, 'delivery_phase_id' => 5, 'target_duration_in_seconds' => $getDeliveryRoutes->sla, 'status' => 'not started'],
            // ];

            // WorkOrderEvent::insert($eventData);

            // WorkOrderEvent::insert($SLAeventData);

            Delivery::findOrFail($validated['delivery_id'])->update([
                'status_id' => 2,
                'updated_by' => auth()->id(),
            ]);

            return $workOrder;
        });

        return response()->json($workOrder, 201);
    }

    public function storeExternalWO(Request $request)
    {
        $data = $request->calculatedata;

        $validated = $request->validate([
            'work_order_type_id' => 'required|integer',
            'unit_id' => 'required|integer',
            'work_order_status' => 'required|exists:work_order_statuses,id',
            'delivery_id' => 'required|integer',
        ]);

        $workOrder = DB::transaction(function () use ($validated, $data) {
            $validated['payment_status'] = 'paid';
            $validated['created_by'] = auth()->id();
            $validated['assigned_at'] = now();
            $validated['code'] = $this->generateWorkOderCode(
                $validated['delivery_id'],
                $validated['work_order_type_id']
            );

            $workOrder = WorkOrder::create($validated);

            VendorWorkOrder::create([
                'vendor_id'     => $data['vendor_id'],
                'price'         => $data['price'],
                'total'         => $data['total'],
                'created_by'    => auth()->id(),
            ]);

            // $getDeliveryRoutes = DeliveryRoute::where('delivery_id', $validated['delivery_id'])->first();

            // $eventData = [
            //     ['work_order_id' => $workOrder->id, 'delivery_phase_id' => 2, 'target_time' => $getDeliveryRoutes->target_load_date, 'status' => 'not started'],
            //     ['work_order_id' => $workOrder->id, 'delivery_phase_id' => 4, 'target_time' => $getDeliveryRoutes->target_load_complete_date, 'status' => 'not started'],
            //     ['work_order_id' => $workOrder->id, 'delivery_phase_id' => 6, 'target_time' => $getDeliveryRoutes->target_unload_date, 'status' => 'not started'],
            //     ['work_order_id' => $workOrder->id, 'delivery_phase_id' => 8, 'target_time' => $getDeliveryRoutes->target_unload_complete_date, 'status' => 'not started']
            // ];

            // $SLAeventData = [
            //     ['work_order_id' => $workOrder->id, 'delivery_phase_id' => 5, 'target_duration_in_seconds' => $getDeliveryRoutes->sla, 'status' => 'not started'],
            // ];

            // WorkOrderEvent::insert($eventData);

            // WorkOrderEvent::insert($SLAeventData);

            Delivery::findOrFail($validated['delivery_id'])->update([
                'status_id' => 3,
                'updated_by' => auth()->id(),
            ]);

            return $workOrder;
        });

        return response()->json($workOrder, 201);
    }

    public function storeDriverAction(Request $request)
    {
        $filepath = null;

        if (isset($request->pic)) {
            $filepath = $this->storeBase64($request->pic, $request->pic_name);
        }

        $event = WorkOrderEvent::create([
            'work_order_id'               => $request->work_order_id,
            'delivery_phase_id'           => $request->delivery_phase_id,
            'target_time'                 => null,
            'actual_time'                 => now(),
            'target_duration_in_seconds'  => null,
            'actual_duration_in_seconds'  => null,
            'actual_result'               => null,
            'created_source'              => 'driver',
            'created_by'                  => auth()->id(),
            'created_date'                => now(),
            'pic'                         => $filepath,
            'status'                      => 'final',
            'remarks'                     => $request->remarks,
            'queue_number'                => $request->queue_number ?? null
        ]);

        return response()->json([
            'success' => true,
            'data'    => $event
        ]);
    }

    public function storeDeliveryProblem(Request $request)
    {
        $validated = $request->validate([
            'delivery_id'     => 'required|integer',
            'type'            => 'required|string|max:50',
            'problem_details' => 'required|string',
            'customer_notes'  => 'nullable|string',
            'vendor_notes'    => 'nullable|string',
            'company_notes'   => 'nullable|string',
        ]);

        $deliveryProblem = DeliveryProblemNote::updateOrCreate(
            ['id' => $request->id],
            [
                'delivery_id'     => $validated['delivery_id'],
                'type'            => $validated['type'],
                'problem_details' => $validated['problem_details'],
                'customer_notes'  => $validated['customer_notes'] ?? null,
                'vendor_notes'    => $validated['vendor_notes'] ?? null,
                'company_notes'   => $validated['company_notes'] ?? null,
                'created_by'      => auth()->id(),
                'created_date'    => now(),
            ]
        );

        if ($request->has('supportingDocs') && is_array($request->supportingDocs)) {
            foreach ($request->supportingDocs as $attachment) {
                if (isset($attachment['base64']) && isset($attachment['file_name'])) {
                    $filepath = $this->storeBase64($attachment['base64'], $attachment['file_name']);
                    DeliveryProblemSupportingDoc::create([
                        'delivery_problem_id' => $deliveryProblem->id,
                        'file_name'           => $attachment['file_name'],
                        'file_path'           => $filepath,
                        'created_by'          => auth()->id(),
                        'created_at'          => now(),
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $deliveryProblem
        ]);
    }

    private function storeBase64($base64, $filename)
    {
        if (preg_match('/^data:\w+\/\w+;base64,/', $base64)) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
        }

        $decoded = base64_decode($base64);

        if (!Storage::disk('public')->exists('uploads')) {
            Storage::disk('public')->makeDirectory('uploads');
        }

        $path = 'uploads/' . $filename;
        Storage::disk('public')->put($path, $decoded);

        return Storage::url($path);
    }
}
