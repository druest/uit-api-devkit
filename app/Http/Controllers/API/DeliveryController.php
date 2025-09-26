<?php

namespace App\Http\Controllers\API;

use App\Models\DeliveryType;
use App\Models\Destination;
use App\Models\Origin;
use App\Models\Route;
use App\Models\Delivery;
use App\Models\Customer;
use App\Models\DeliveryRoute;
use App\Models\DeliveryTermsCondition;
use App\Models\DestinationPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryController extends BaseController
{
    // GET /api/deliveries
    public function index()
    {
        return Delivery::with(['status', 'routes.destination', 'customer', 'creator', 'updater'])->paginate(20);
    }

    public function getOutstandingWorkOrder()
    {
        return Delivery::where('status_id', 1)->with(['status', 'routes.origin', 'routes.destination', 'customer', 'creator', 'updater'])->paginate(20);
    }

    // POST /api/deliveries
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

        $validated['price'] = $this->getPriceById($validated['destination_id']);

        $validated['created_by'] = Auth::id();

        $validated['status_id'] = 1;

        $delivery = Delivery::create($validated);

        return response()->json($delivery->load(['status', 'creator']), 201);
    }

    // GET /api/deliveries/{id}
    public function show($id)
    {
        return Delivery::with([
            'customer', 'termsconditions', 'routes', 'deliveryType', 'status', 'creator', 'updater', 'workOrders.type', 'workOrders.creator',
            'workOrders.status', 'workOrders.expenses', 'workOrders.unit.vendor', 'workOrders.driver'
        ])->findOrFail($id);
    }

    // PUT /api/deliveries/{id}
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

    // DELETE /api/deliveries/{id}
    public function destroy($id)
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->delete();

        return response()->json(['message' => 'Delivery deleted']);
    }

    private function getPriceById($destinationId)
    {
        $price = DestinationPrice::where('destination_id', $destinationId)->firstOrFail();

        return $price->price;
    }

    private function generateDeliveryId($customerID, $deliveryDate)
    {
        $customer = Customer::with(['creator', 'updater'])->findOrFail($customerID);

        $prefix = 'UDO';
        $desiredDate = Carbon::parse($deliveryDate);
        $timestamp = $desiredDate->format('Ymd');
        $customerCode = $customer->code;
        $today = $desiredDate->toDateString();

        $countToday = Delivery::whereDate('delivery_date', $today)->count();
        $sequence = $countToday + 1;
        $sequencePart = str_pad($sequence, 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$customerCode}-{$timestamp}-{$sequencePart}";
    }

    public function deliveryTypes()
    {
        return $this->sendResponse(DeliveryType::with(['creator', 'updater'])->paginate(20), "Success");
    }

    public function routes($id)
    {
        return $this->sendResponse(Route::where('destination_id', $id)->get(), "Success");
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
            'delivery_date' => 'required|date',
            'customer_delivery_number' => 'nullable|string|max:255',
            'customer_id' => 'required|integer|exists:customers,id',
            'delivery_type_id' => 'required|integer|exists:delivery_types,id',
            'notes' => 'nullable|string',
            'useSecondaryPrice' => 'nullable|string'
        ])->validate();

        $validatedRoutes = validator($request->all(), [
            'routeData.*.origin_id'       => 'required|integer|exists:origins,id',
            'routeData.*.destination_id'  => 'required|integer|exists:destinations,id',
            'routeData.*.route_id'        => 'required|integer|exists:routes,id',
            'routeData.*.target_load_date'        => 'nullable|date',
            'routeData.*.target_unload_date'        => 'nullable|date',
        ])->validate();

        $validatedTnC = validator($request->all(), [
            'tncData.*.tnc_name'       => 'required|string|max:255',
            'tncData.*.tnc_description'  => 'required|string|max:255',
        ])->validate();

        $delivery = DB::transaction(function () use (&$delivery, $validatedDelivery, $validatedRoutes, $validatedTnC) {
            $delivery = Delivery::create([
                ...Arr::only($validatedDelivery, (new Delivery)->getFillable()),
                'delivery_code' => $this->generateDeliveryId($validatedDelivery['customer_id'], $validatedDelivery['delivery_date']),
                'status_id' => 1,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $cleanedRoutes = collect($validatedRoutes['routeData'])->map(function ($route, $index) use ($validatedDelivery, $delivery) {
                return [
                    'origin_id' => $route['origin_id'],
                    'destination_id' => $route['destination_id'],
                    'route_id' => $route['route_id'],
                    'amount' => ($index === 0 || ($validatedDelivery['delivery_type_id'] == 2 && $validatedDelivery['useSecondaryPrice'] === "Yes"))
                        ? $this->getPriceById($route['destination_id'])
                        : 0,
                    'delivery_id' => $delivery->id,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ];
            })->toArray();

            $cleanedTnC = collect($validatedTnC['tncData'])->map(function ($tnc) use ($delivery) {
                return [
                    'tnc_name' => $tnc['tnc_name'],
                    'tnc_description' => $tnc['tnc_description'],
                    'delivery_id' => $delivery->id,
                    'created_by' => auth()->id(),
                ];
            })->toArray();

            DeliveryRoute::insert($cleanedRoutes);

            DeliveryTermsCondition::insert($cleanedTnC);

            return $delivery;
        });

        return response()->json($delivery->load(['status', 'creator']), 201);
    }

    public function selectRoutesById($id)
    {
        return $this->sendResponse(DeliveryRoute::with(['origin', 'destination', 'route', 'origin.city', 'destination.city'])->where('delivery_id', $id)->get(), "Success");
    }
}
