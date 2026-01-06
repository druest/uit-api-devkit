<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CustomerExpenseParam;
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
use App\Models\WorkOrderAcceptance;
use App\Models\WorkOrderCheckpoint;
use App\Models\WorkOrderReport;
use App\Models\WorkOrderRequest;
use App\Models\WorkOrderRequestExpense;
use App\Models\WorkOrderRoute;
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

    public function submitAcceptance(Request $request)
    {
        $acceptance = WorkOrderAcceptance::create([
            'work_order_id'      => $request->work_order_id,
            'acceptances_status' => $request->acceptances_status,
            'acceptances_date'   => $request->acceptances_date, // will be cast to datetime
            'remarks'            => $request->remarks,
            'created_by'         => auth()->id(),
        ]);

        return response()->json($acceptance);
    }

    public function index()
    {
        return WorkOrder::with(['status', 'type', 'unit', 'driver', 'expenses', 'creator', 'updater'])->paginate(20);
    }

    public function getDeliveryByUserID()
    {
        $userID = auth()->id();

        return WorkOrder::where('driver_id', $userID)
            ->with(['delivery', 'delivery.routes', 'delivery.routes', 'delivery.routes.multiDest1', 'delivery.routes.multiDest2', 'delivery.routes.multiOrigin1', 'delivery.routes.multiOrigin2', 'delivery.routes.voucherUssage.voucher.origin', 'delivery.routes.voucherUssage.voucher.destination', 'delivery.customer', 'woCal',  'woCal.expenseType', 'unit', 'driver', 'acceptance'])
            ->paginate(20);
    }

    public function getReports()
    {
        $reports = WorkOrderReport::with([
            'pictures',
            'workOrder',
            'workOrder.delivery',
            'workOrder.unit',
            'workOrder.driver',
            'resolvedBy'
        ])->get();

        $reports->each(function ($report) {
            $report->pictures = $report->pictures?->map(function ($pic) {
                $pic->path = asset($pic->file_path);
                return $pic;
            });
        });

        return $reports;
    }

    public function getByStatus($id)
    {
        if ($id == 0) {
            return WorkOrder::with(['status', 'type', 'unit', 'driver', 'expenses', 'creator', 'updater'])->paginate(20);
        }
        return WorkOrder::where('work_order_status', $id)->with(['status', 'type', 'unit', 'driver', 'expenses', 'creator', 'updater'])->paginate(20);
    }

    public function getUJValues($id)
    {
        $getConfigData = ExpenseType::where('is_di_config', 1)
            ->orderBy('sequence', 'desc')
            ->get(); // execute query
        return $getConfigData;
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
            'checkpoints',
            'workOrderEvents.deliveryPhase',
            'woCal',
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

        $prefix = 'DI';
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

    private function generateWorkOderRequestCode()
    {
        //$vendor = Vendor::with(['creator', 'updater'])->findOrFail($vendorID);

        $prefix = 'DIX';
        $timestamp = now()->format('Ymd');
        $today = now()->toDateString();
        $countToday = WorkOrderRequest::whereDate('created_at', $today)->count();
        $sequence = $countToday + 1;
        $sequencePart = str_pad($sequence, 3, '0', STR_PAD_LEFT);
        return "{$prefix}-{$timestamp}-{$sequencePart}";
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
            'routes.multiOrigin1', 'routes.multiDest1', 'routes.multiDest2', 'routes.multiOrigin2',
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
            'routes.multiOrigin1', 'routes.multiDest1', 'routes.multiDest2', 'routes.multiOrigin2',
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
            'deliveryWaybills',
            'status',
            'workOrders',
            'workOrders.unit',
            'workOrders.driver',
            'workOrders.workOrderEvents',
            'workOrders.checkpoints',
            'workOrders.second_driver',
            'workOrders.old_driver',
            'workOrders.old_second_driver',
            'routes.destination',
            'routes.deliveryType',
            'deliveryPlan',
            'routes.multiOrigin1', 'routes.multiDest1', 'routes.multiDest2', 'routes.multiOrigin2',
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
            'routes.multiOrigin1', 'routes.multiDest1', 'routes.multiDest2', 'routes.multiOrigin2',
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

    public function storeOtherDI(Request $request)
    {
        $originData = $request->origin;
        $destinationData = $request->destination;
        $amountData = $request->amount;

        $workOrder = null;

        DB::transaction(function () use ($request, $originData, $destinationData, &$workOrder, $amountData) {
            $workOrder = WorkOrderRequest::create([
                'number' => $this->generateWorkOderRequestCode(),
                'unit_id' => (int) $request['unit_id'],
                'driver_id' => (int) ($request['driver_id'] ?? 0),
                'schedule' => (string) $request['schedule'],
                'purpose' => (string) $request['purpose'],
                'distance_value' => (float) $request['distance_value'],
                'distance_display' => (string) $request['distance_display'],
                'distance_calc_value' => (float) $request['distance_calc_value'],
                'distance_calc_display' => (string) $request['distance_calc_display'],
                'duration_value' => (float) $request['duration_value'],
                'duration_display' => (string) $request['duration_display'],
                'duration_calc_value' => (float) $request['duration_calc_value'],
                'duration_calc_display' => (string) $request['duration_calc_display'],
                'notes' => (string) $request['notes'] ?? null,
                'status' => 'Submitted',
                'created_by' => auth()->id(),
            ]);

            WorkOrderRoute::create([
                'work_order_request_id' => $workOrder->id,
                'route_type'            => 'origin',
                'address_name'          => (string) $originData['address_name'],
                'full_address'          => (string) $originData['full_address'],
                'lat'                   => (float) $originData['lat'],
                'lng'                   => (float) $originData['lng'],
                'created_by'            => auth()->id(),
            ]);

            WorkOrderRoute::create([
                'work_order_request_id' => $workOrder->id,
                'route_type'            => 'destination',
                'address_name'          => (string) $destinationData['address_name'],
                'full_address'          => (string) $destinationData['full_address'],
                'lat'                   => (float) $destinationData['lat'],
                'lng'                   => (float) $destinationData['lng'],
                'created_by'            => auth()->id(),
            ]);

            WorkOrderRequestExpense::create([
                'work_order_request_id' => $workOrder->id,
                'expense_type'          => 10,
                'amount'                => $amountData['fuel_fee'] ?? 0,
                'created_by'            => auth()->id(),
            ]);

            if (isset($amountData['toll_fee'])) {
                WorkOrderRequestExpense::create([
                    'work_order_request_id'     => $workOrder->id,
                    'expense_type'              => 2,
                    'amount'                    => $amountData['toll_fee'] ?? 0,
                    'created_by'                => auth()->id(),
                ]);
            }

            if (isset($amountData['driver_fee'])) {
                WorkOrderRequestExpense::create([
                    'work_order_request_id'     => $workOrder->id,
                    'expense_type'              => 1,
                    'amount'                    => $amountData['driver_fee'] ?? 0,
                    'created_by'                => auth()->id(),
                ]);
            }
        });

        return response()->json([
            'message' => 'Work order request created successfully',
            'data' => $workOrder
        ], 201);
    }

    public function selectOtherDI($id)
    {
        return WorkOrderRequest::with('routes', 'expense', 'expense.expenseType', 'unit', 'driver')->findOrFail($id);
    }

    public function getAllOtherDI()
    {
        return WorkOrderRequest::with('routes', 'expense', 'expense.expenseType', 'unit', 'driver')->get();
    }

    public function getOutstandingOtherDI()
    {
        return WorkOrderRequest::where('status', 'Submitted')->with('routes', 'expense', 'expense.expenseType', 'unit', 'driver')->get();
    }

    public function store(Request $request)
    {
        $data = $request->calculatedata;
        $checkpointData = $request->checkpointData;

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

        $workOrder = DB::transaction(function () use ($validated, $data, $checkpointData) {
            $validated['payment_status'] = 'unpaid';
            $validated['created_by']     = auth()->id();
            $validated['assigned_at']    = now();
            $validated['trf_available']  = 0;
            $validated['code']           = $this->generateWorkOderCode(
                $validated['delivery_id'],
                $validated['work_order_type_id']
            );

            $workOrder = WorkOrder::create($validated);

            foreach ($data as $expense) {
                WorkOrderCalcExpense::create([
                    'work_order_id'         => $workOrder->id,
                    'expense_type_id'       => $expense['expense_type_id'] ?? 0,
                    'amount'                => $expense['amount'] ?? 0,
                    'amount_multi_origin_1' => $expense['amount_multi_origin_1'] ?? 0,
                    'amount_multi_origin_2' => $expense['amount_multi_origin_2'] ?? 0,
                    'amount_multi_dest_1'   => $expense['amount_multi_dest_1'] ?? 0,
                    'amount_multi_dest_2'   => $expense['amount_multi_dest_2'] ?? 0,
                    'note'                  => $expense['note'] ?? "",
                    'created_by'            => auth()->id(),
                ]);
            }

            foreach ($checkpointData as $checkpoint) {
                WorkOrderCheckpoint::create([
                    'work_order_id'     => $workOrder->id,
                    'city_label'        => $checkpoint['city_label'],
                    'lat'               => $checkpoint['lat'],
                    'lng'               => $checkpoint['lng'],
                    'checkpoint_label'  => $checkpoint['checkpoint_label'],
                    'created_by'        => auth()->id(),
                    'created_at'        => now(),
                ]);
            }

            Delivery::findOrFail($validated['delivery_id'])->update([
                'status_id'  => 2,
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

    public function processUJ($id)
    {
        $workOrder = WorkOrder::findOrFail($id);

        $workOrder->update([
            'trf_available' => true, // clearer than 1
            'updated_by'    => auth()->id(),
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Work order updated successfully',
            'workOrder' => $workOrder, // optional: return updated data
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
