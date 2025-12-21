<?php

namespace App\Http\Controllers\API;

use App\Models\City;
use App\Models\DeliveryType;
use App\Models\Destination;
use App\Models\Origin;
use App\Models\Route;
use App\Models\Delivery;
use App\Models\Customer;
use App\Models\CustomerDeliveryType;
use App\Models\DeliveryPicture;
use App\Models\DeliveryRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\DestinationExpense;
use App\Models\DeliveryPlan;
use App\Models\RoutesVoucherUssage;
use App\Models\RouteVoucher;
use App\Models\VendorRoutePricing;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Validator;
use Exception;

class DeliveryController extends BaseController
{
    public function index()
    {
        return Delivery::with(['status', 'workOrders', 'workOrders.unit', 'workOrders.driver', 'workOrders.second_driver', 'workOrders.old_driver', 'workOrders.old_second_driver', 'routes.destination', 'routes.deliveryType', 'routes.multiDest1', 'routes.multiDest2', 'routes.multiOrigin1', 'routes.multiOrigin2', 'routes.voucherUssage.voucher.origin', 'routes.voucherUssage.voucher.destination',  'deliveryPlan', 'deliveryPlan.nextCity', 'deliveryPlan.unit', 'deliveryPlan.vendor', 'routes.destination.city', 'routes.destination.origin.city', 'customer', 'creator', 'updater'])->paginate(20);
    }

    public function getSummaryAR()
    {
        $deliveries = Delivery::with([
            'status', 'workOrders', 'workOrders.unit', 'workOrders.driver', 'routes.secondaryOrigin.city', 'routes.multiDest1.city', 'routes.multiDest2.city', 'routes.multiDest3.city', 'routes.voucherUssage.voucher.origin', 'routes.voucherUssage.voucher.destination',
            'workOrders.second_driver', 'workOrders.old_driver', 'workOrders.old_second_driver',
            'routes.destination', 'routes.deliveryType', 'deliveryPlan', 'deliveryPlan.nextCity', 'deliveryPlan.unit', 'deliveryPlan.vendor',
            'routes.destination.city', 'routes.destination.origin.city',
            'customer', 'creator', 'updater'
        ])->paginate(20);

        $deliveries->getCollection()->transform(function ($delivery) {
            $delivery->destination_price = $this->calcDestinationPrice($delivery->routes[0]->destination_id, $delivery->routes[0]->delivery_type, $delivery->customer_id);
            return $delivery;
        });

        return $deliveries;
    }

    public function getSummaryAP()
    {
        $deliveries = Delivery::with([
            'status', 'workOrders', 'workOrders.unit', 'workOrders.driver',
            'workOrders.second_driver', 'workOrders.old_driver', 'workOrders.old_second_driver',
            'routes.destination', 'routes.deliveryType', 'deliveryPlan', 'deliveryPlan.nextCity', 'deliveryPlan.unit', 'deliveryPlan.vendor',
            'routes.destination.city', 'routes.destination.origin.city',
            'customer', 'creator', 'updater'
        ])->whereHas('deliveryPlan', function ($query) {
            $query->where('assignment_type', 'WO');
        })->paginate(20);

        $deliveries->getCollection()->transform(function ($delivery) {
            $delivery->destination_price = $this->calcVendorPrice(
                $delivery->deliveryPlan->vendor_id,
                $delivery->routes[0]->destination->origin->city->id ?? null,
                $delivery->routes[0]->destination->city->id ?? null
            );
            $delivery->vendor = $delivery->deliveryPlan->vendor_id;
            $delivery->originn = $delivery->routes[0]->destination->origin->city->id;
            $delivery->dest = $delivery->routes[0]->destination->city->id;

            return $delivery;
        });

        return $deliveries;
    }

    private function calcVendorPrice($vendorID, $originID, $destinationID)
    {
        $getPrice = VendorRoutePricing::where(['vendor_id' => $vendorID, 'origin_city' => $originID, 'destination_city' => $destinationID])->firstOrFail();
        $sub_price = $getPrice->price;
        $final_price = 0;
        $tax_amount = 0;
        $tax_23_amount = 0;

        if (true) {
            $tax_amount = $sub_price * 11 / 100;
        }

        if (false) {
            $tax_23_amount = $sub_price * 2 / 100;
        }

        $final_price = $sub_price + $tax_amount + $tax_23_amount;

        return ['price' => $sub_price, "final_price" => $final_price, "tax" => $tax_amount, "tax_23" => $tax_23_amount];
    }

    private function getTripPackageByDestinationID($id)
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
                    'total_amount_display' => 'Rp. ' . number_format($group->sum('amount')),
                    'count' => $group->count(),
                ];
            })->values();
        return $summary;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'delivery_date' => 'required|date',
            'customer_delivery_number' => 'nullable|string',
            'customer_id' => 'required|integer',
            'origin_id' => 'required|integer',
            'destination_id' => 'required|integer',
            'route_id' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

        $validated['delivery_code'] = $this->generateDeliveryId($validated['customer_id'], $validated['delivery_date']);

        $validated['created_by'] = Auth::id();

        $validated['status_id'] = 1;

        $delivery = Delivery::create($validated);

        return response()->json($delivery->load(['status', 'creator']), 201);
    }

    public function show($id)
    {
        $delivery = Delivery::with([
            'customer', 'customer.parent', 'termsconditions', 'routes', 'routes.origin', 'routes.origin.city',  'routes.destination', 'routes.deliveryType', 'routes.destination.city', 'routes.route', 'routes.multiOrigin1', 'routes.multiDest1', 'routes.multiDest2', 'routes.multiOrigin2', 'deliveryType', 'status', 'creator', 'updater', 'workOrders.type', 'workOrders.creator', 'routes.voucherUssage.voucher.origin', 'routes.voucherUssage.voucher.destination', 'workOrders.status', 'workOrders.expenses', 'workOrders.unit.vendor', 'workOrders.driver', 'workOrders.workOrderEvents', 'workOrders.woCal'
        ])->findOrFail($id);

        $pictures = DeliveryPicture::where('ref_id', $delivery->id)->get();

        $pictures = $pictures->map(function ($pic) {
            $pic->url = asset(ltrim($pic->file_path, '/'));
            return $pic;
        });

        $delivery->deliveryPictures = $pictures;

        return $delivery;
    }

    public function update(Request $request, $id)
    {
        $delivery = Delivery::findOrFail($id);

        $validated = $request->validate([
            'delivery_date' => 'sometimes|date',
            'delivery_code' => 'sometimes|string|unique:deliveries,delivery_code,' . $id,
            'customer_delivery_number' => 'nullable|string',
            'customer_id' => 'sometimes|integer',
            'status_id' => 'sometimes|exists:delivery_statuses,id',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = Auth::id();

        $delivery->update($validated);

        return response()->json($delivery->load(['status', 'updater']));
    }

    public function destroy($id)
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->delete();

        return response()->json(['message' => 'Delivery deleted']);
    }

    private function getPriceById($id, $typeID, $customerID)
    {
        $price = $price = RoutesVoucherUssage::where(['id' => $id, 'reference_type' => $typeID, 'reference_id' => $customerID, 'category' => 'customer'])->with(['voucher'])->firstOrFail();

        return $price->amount;
    }

    private function generateDeliveryId($customerID, $deliveryDate)
    {
        $customer = Customer::with(['creator', 'updater'])->findOrFail($customerID);

        $prefix = 'UDO';
        $desiredDate = Carbon::parse($deliveryDate);
        $timestamp = $desiredDate->format('ymd');
        $customerCode = $customer->code;
        $today = $desiredDate->toDateString();

        $countToday = Delivery::whereDate('delivery_date', $today)->count();
        $sequence = $countToday + 1;
        $sequencePart = str_pad($sequence, 3, '0', STR_PAD_LEFT);

        return "{$customerCode}-{$timestamp}-{$sequence}";
    }

    public function deliveryTypes()
    {
        return $this->sendResponse(DeliveryType::with(['creator', 'updater'])->paginate(20), "Success");
    }

    public function routes($id)
    {
        return $this->sendResponse(Route::where('destination_id', $id)->get(), "Success");
    }

    public function deliveryType($id)
    {
        return $this->sendResponse(CustomerDeliveryType::where('customer_id', $id)->get(), "Success");
    }

    public function getRouteByCustomer($id)
    {
        return $this->sendResponse(RoutesVoucherUssage::with(['voucher.origin', 'voucher.destination'])->where(['category' => 'customer', 'reference_id' => $id])->get(), "Success");
    }

    public function origins($id)
    {
        return $this->sendResponse(Origin::with(['city'])->where('customer_id', $id)->get(), "Success");
    }

    public function destinations($id)
    {
        return $this->sendResponse(Destination::with(['city'])->where('origin_id', $id)->get(), "Success");
    }

    public function insertDeliveryOrder(Request $request)
    {
        $validatedDelivery = validator($request->input('deliveryData'), [
            'confirmation_date' => 'required|date',
            'delivery_date' => 'required|date',
            'remarks' => 'nullable|string',
            'customer_delivery_number' => 'nullable|string|max:255',
            'customer_id' => 'required|integer|exists:customers,id',
            'delivery_type_id' => 'required|integer|exists:delivery_types,id',
            'notes' => 'nullable|string',
            'useSecondaryPrice' => 'nullable|string',
        ])->validate();

        $validatedRoutes = validator($request->all(), [
            'routeData.*.origin_id'       => 'required|integer',
            'routeData.*.destination_id'  => 'required|integer',
            'routeData.*.route_id' => 'required|integer',
            'routeData.*.sla' => 'required|integer',
            'routeData.*.delivery_type' => 'required|integer',
            'routeData.*.target_arrival_date' => 'required|date',
            'routeData.*.target_load_date' => 'required|date',
            'routeData.*.target_unload_date' => 'required|date',
            'routeData.*.multi_dest_1' => 'nullable',
            'routeData.*.multi_dest_2' => 'nullable',
            'routeData.*.multi_origin_1' => 'nullable',
            'routeData.*.multi_origin_2' => 'nullable',
            'routeData.*.unit_type' => 'nullable',
            'routeData.*.use_multi_origin' => 'nullable',
            'routeData.*.use_multi_destination' => 'nullable',
            'routeData.*.use_multi_origin_2' => 'nullable',
            'routeData.*.use_multi_destination_2' => 'nullable',
            'routeData.*.amount_multi_origin_1' => 'nullable',
            'routeData.*.amount_multi_origin_2' => 'nullable',
            'routeData.*.amount_multi_dest_1' => 'nullable',
            'routeData.*.amount_multi_dest_2' => 'nullable',
            'routeData.*.discount_tuslah' => 'nullable',
            'routeData.*.amount_dt' => 'nullable',
            'routeData.*.amount' => 'nullable',
        ])->validate();

        $delivery = DB::transaction(function () use (&$delivery, $validatedDelivery, $validatedRoutes) {
            $delivery = Delivery::create([
                ...Arr::only($validatedDelivery, (new Delivery)->getFillable()),
                'delivery_code' => $this->generateDeliveryId($validatedDelivery['customer_id'], $validatedDelivery['delivery_date']),
                'status_id' => 1,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $cleanedRoutes = collect($validatedRoutes['routeData'])->map(function ($route, $index) use ($validatedDelivery, $delivery) {
                return [
                    ...$route,
                    'delivery_id' => $delivery->id,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ];
            })->toArray();

            DeliveryRoute::insert($cleanedRoutes);

            return $delivery;
        });

        return response()->json($delivery->load(['status', 'creator']), 201);
    }

    public function selectRoutesById($id)
    {
        return $this->sendResponse(DeliveryRoute::with(['origin', 'destination', 'route', 'origin.city', 'destination.city'])->where('delivery_id', $id)->get(), "Success");
    }

    public function getDestinationPriceById($id, $typeID, $customerID)
    {
        return $this->sendResponse($this->calcDestinationPrice($id, $typeID, $customerID), "Success");
    }

    private function calcDestinationPrice($id, $typeID, $customerID)
    {
        $price = RoutesVoucherUssage::where(['id' => $id, 'reference_type' => $typeID, 'reference_id' => $customerID, 'category' => 'customer'])->with(['voucher'])->firstOrFail();
        $customer = Customer::where('id', $customerID)->firstOrFail();

        $sub_price = $price->amount;
        $final_price = 0;
        $tax_amount = 0;
        $tax_23_amount = 0;

        if ($customer->is_taxable) {
            $tax_amount = $sub_price * 11 / 100;
        }

        if ($customer->requires_pph23) {
            $tax_23_amount = $sub_price * 2 / 100;
        }

        $final_price = $sub_price + $tax_amount + $tax_23_amount;

        $distance_data = $price->voucher;

        return ['price' => $sub_price, "final_price" => $final_price, "tax" => $tax_amount, "tax_23" => $tax_23_amount, "distance_data" => $distance_data];
    }

    public function generateCityData()
    {
        $cities = City::all(); // Use all() for clarity

        foreach ($cities as $city) {
            if (!$city->lat) {
                $result = $this->getSingleCoordinateByCity($city->name, 'AIzaSyBKDaCGNhTbmXSO4vEHLWfJarXjBDbSu_w');

                if ($result) {
                    $city->lat = $result['lat'];
                    $city->lng = $result['lng'];
                    $city->save(); // Use save() to persist changes
                }
            }
        }
    }

    public function generateRouteData()
    {
        ini_set('max_execution_time', 120);
        $cities = RouteVoucher::with(['origin', 'destination'])->get();

        foreach ($cities as $city) {
            if ($city->origin && $city->destination && $city->duration_value == null) {
                $result = $this->getDistanceAndETA(
                    $city->origin->lat . ',' . $city->origin->lng,
                    $city->destination->lat . ',' . $city->destination->lng
                );

                if ($result) {
                    $city->duration_value       = $result['duration_value'];
                    $city->duration_display     = $result['duration'];
                    $city->distance_value       = $result['distance_value'];
                    $city->distance_display     = $result['distance'];
                    $city->save();
                }
            }
        }
    }

    function getSingleCoordinateByCity(string $city, string $apiKey): ?array
    {
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($city) . "&key=" . $apiKey;

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if ($data['status'] !== 'OK' || empty($data['results'])) {
            return null;
        }

        $location = $data['results'][0]['geometry']['location'];

        return [
            'lat' => $location['lat'],
            'lng' => $location['lng'],
            'address' => $data['results'][0]['formatted_address']
        ];
    }

    public function getDistanceAndETA($origin, $destination)
    {
        $apiKey = 'AIzaSyBKDaCGNhTbmXSO4vEHLWfJarXjBDbSu_w';
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?" . http_build_query([
            'origins' => $origin,
            'destinations' => $destination,
            'units' => 'metric',
            'mode' => 'driving',
            'key' => $apiKey
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return null;
        }

        curl_close($ch);
        $data = json_decode($response, true);

        if ($data['rows'][0]['elements'][0]['status'] !== 'OK') {
            return null;
        }

        $distance = $data['rows'][0]['elements'][0]['distance']['value'] * 1.1; // add 10%
        $distance_cal = ceil($distance / 1000);

        $sla_seconds = $data['rows'][0]['elements'][0]['duration']['value'] * 2.1;
        $sla_minutes = ceil($sla_seconds / 60);

        return [
            'distance'        => $distance_cal . ' km',
            'distance_value'  => $distance_cal,
            'duration_value'  => $sla_minutes,
            'duration'        => $sla_minutes . ' min(s)'
        ];
    }

    public function getOutStandingPlan()
    {
        return Delivery::with([
            'status', 'routes.destination', 'routes.deliveryType', 'deliveryPlan', 'deliveryPlan.nextCity', 'deliveryPlan.unit', 'workOrders', 'workOrders.driver', 'workOrders.second_driver',  'deliveryPlan.vendor', 'routes.destination.city', 'routes.multiDest1', 'routes.multiDest2', 'routes.multiOrigin1',
            'routes.multiOrigin2', 'routes.destination.origin.city', 'routes.voucherUssage.voucher.origin', 'routes.voucherUssage.voucher.destination', 'customer', 'creator', 'updater'
        ])->get();
    }

    public function showForDI($id)
    {
        $result = Delivery::with([
            'status', 'routes.destination', 'routes.deliveryType', 'deliveryPlan', 'deliveryPlan.nextCity', 'deliveryPlan.unit', 'deliveryPlan.vendor', 'routes.destination.city', 'routes.destination.origin.city', 'customer', 'creator', 'updater', 'routes.multiDest1', 'routes.multiDest2', 'routes.multiOrigin1', 'routes.multiOrigin2', 'routes.voucherUssage.voucher.origin', 'routes.voucherUssage.voucher.destination', 'workOrders.type', 'workOrders.creator', 'workOrders.status', 'workOrders.expenses', 'workOrders.unit.vendor', 'workOrders.driver'
        ])->findOrFail($id);

        return $result;
    }

    public function showForWO($id)
    {
        $result = Delivery::with([
            'status', 'routes.destination', 'routes.deliveryType', 'deliveryPlan', 'deliveryPlan.nextCity', 'deliveryPlan.unit', 'deliveryPlan.vendor', 'routes.destination.city', 'routes.destination.origin.city', 'customer', 'creator', 'updater', 'routes.multiDest1', 'routes.multiDest2', 'routes.multiOrigin1', 'routes.multiOrigin2', 'routes.voucherUssage.voucher.origin', 'routes.voucherUssage.voucher.destination', 'workOrders.type', 'workOrders.creator', 'workOrders.status', 'workOrders.expenses', 'workOrders.unit.vendor', 'workOrders.driver'
        ])->findOrFail($id);

        if ($result->deliveryPlan && $result->deliveryPlan->vendor) {
            $result->price = $this->getVendorPrice($result->deliveryPlan->vendor_id, $result->routes[0]->voucherUssage->voucher_id ?? null);
        }

        return $result;
    }

    private function getVendorPrice($id, $origin)
    {
        $result = RoutesVoucherUssage::where([
            'reference_id' => $id,
            'voucher_id' => $origin,
        ])->firstOrFail();

        return $result;
    }

    public function getByCustomer($id)
    {
        return Delivery::where('customer_id', $id)->with(['status', 'workOrders', 'workOrders.unit', 'workOrders.driver', 'workOrders.second_driver', 'workOrders.old_driver', 'workOrders.old_second_driver', 'routes.destination', 'routes.deliveryType', 'routes.multiDest1', 'routes.multiDest2', 'routes.multiOrigin1', 'routes.multiOrigin2', 'routes.voucherUssage.voucher.origin', 'routes.voucherUssage.voucher.destination',  'deliveryPlan', 'deliveryPlan.nextCity', 'deliveryPlan.unit', 'deliveryPlan.vendor', 'routes.destination.city', 'routes.destination.origin.city', 'customer', 'creator', 'updater'])->paginate(20);
    }

    public function getOutstandingWorkOrder()
    {
        $deliveries = Delivery::with([
            'status', 'routes.destination', 'routes.deliveryType', 'deliveryPlan', 'deliveryPlan.nextCity', 'workOrders', 'workOrders.driver', 'workOrders.second_driver', 'deliveryPlan.unit', 'deliveryPlan.vendor', 'routes.destination.city', 'routes.destination.origin.city', 'customer', 'creator', 'updater', 'routes.multiOrigin1', 'routes.multiOrigin2', 'routes.multiDest1', 'routes.multiDest2', 'routes.voucherUssage.voucher.origin', 'routes.voucherUssage.voucher.destination',
        ])
            ->whereHas('deliveryPlan', function ($query) {
                $query->where('assignment_type', 'DI');
            })
            ->get();

        return $deliveries;
    }

    public function getOutstandingSubcontract()
    {

        $deliveries = Delivery::with([
            'status', 'routes.destination', 'routes.deliveryType', 'deliveryPlan', 'deliveryPlan.nextCity', 'deliveryPlan.unit', 'workOrders', 'workOrders.driver', 'workOrders.second_driver', 'deliveryPlan.vendor', 'routes.destination.city', 'routes.multiDest1', 'routes.multiDest2', 'routes.multiOrigin1', 'routes.multiOrigin2', 'routes.destination.origin.city', 'customer', 'creator', 'updater',
        ])
            ->whereHas('deliveryPlan', function ($query) {
                $query->where('assignment_type', 'WO');
            })
            ->get();

        return $deliveries;
    }

    public function storeDeliveryPlan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delivery_id'     => 'required|integer',
            'assignment_type' => 'required|string|max:50',
            'unit_id'         => 'nullable|integer',
            'vendor_id'       => 'nullable|integer',
            'planner_notes'   => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plan = DeliveryPlan::create([
            'planner_notes'         => $request->planner_notes,
            'delivery_id'           => $request->delivery_id,
            'assignment_type'       => $request->assignment_type,
            'unit_id'               => $request->unit_id,
            'vendor_id'             => $request->vendor_id,
            'next_city'             => $request->next_city ?? null,
            'next_city_purpose'     => $request->next_city_purpose ?? null,
            'created_by'            => auth()->id(),
        ]);

        return response()->json(['message' => 'Delivery plan created', 'data' => $plan], 201);
    }

    public function updateSCSDate(Request $request)
    {
        $route = DeliveryRoute::findOrFail($request->route_id);

        $route->update([
            'target_load_complete_date' => $request->target_load_complete_date,
            'load_notes' => $request->notes,
        ]);

        if (isset($request->unit)) {
            $wo = WorkOrder::findOrFail($request->work_order_id);
            $wo->update([
                'unit_id' => $request->unit,
                'unit_notes' => $request->unit_notes,
            ]);
        }

        if (isset($request->driver)) {
            $driver = WorkOrder::findOrFail($request->work_order_id);
            $driver->update([
                'old_driver' => $driver->driver_id,
                'driver_id' => $request->driver,
                'notes' => $request->driver_notes,
            ]);
        }

        if (isset($request->secondaryDriver)) {
            $driver = WorkOrder::findOrFail($request->work_order_id);
            $driver->update([
                'old_secondary_driver' => $driver->secondary_driver_id,
                'secondary_driver_id' => $request->secondaryDriver,
                'change_notes' => $request->secondaryDriverNotes,
            ]);
        }

        return response()->json([
            'message' => 'Load complete date updated successfully.',
            'data' => $route,
        ]);
    }

    public function updateFleetData(Request $request)
    {
        if (isset($request->unit)) {
            $wo = WorkOrder::findOrFail($request->work_order_id);
            $wo->update([
                'old_unit' => $wo->unit_id,
                'unit_id' => $request->unit,
                'unit_notes' => $request->unit_notes,
                'is_fleet_unit' => true
            ]);
        }

        if (isset($request->driver)) {
            $driver = WorkOrder::findOrFail($request->work_order_id);
            $driver->update([
                'old_driver' => $driver->driver_id,
                'driver_id' => $request->driver,
                'notes' => $request->driver_notes,
                'is_fleet_driver' => true
            ]);
        }

        if (isset($request->secondaryDriver)) {
            $driver = WorkOrder::findOrFail($request->work_order_id);
            $driver->update([
                'old_secondary_driver' => $driver->secondary_driver_id,
                'secondary_driver_id' => $request->secondaryDriver,
                'change_notes' => $request->secondaryDriverNotes,
                'is_fleet_second_driver' => true
            ]);
        }

        return response()->json([
            'message' => 'Success',
        ]);
    }

    public function updateDCSDate(Request $request)
    {
        $route = DeliveryRoute::findOrFail($request->route_id);

        $route->update([
            'target_unload_complete_date' => $request->target_unload_complete_date,
            'unload_notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Load complete date updated successfully.',
            'data' => $route,
        ]);
    }
}
