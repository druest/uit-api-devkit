<?php

namespace App\Http\Controllers\API;

use App\Models\City;
use App\Models\Customer;
use App\Models\CustomerExpenseParam;
use App\Models\DeliveryPhase;
use App\Models\DeliveryPicture;
use App\Models\DeliveryWaybill;
use App\Models\DeliveryWaybillExpedition;
use App\Models\DeliveryWaybillTracker;
use App\Models\DeliveryWaybillUpload;
use App\Models\Destination;
use App\Models\RoutesVoucherUssage;
use App\Models\RouteVoucher;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorkOrderCheckpoint;
use App\Models\WorkOrderReport;
use App\Models\WorkOrderReportPicture;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class LookupController extends BaseController
{
    public function listCustomer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('MyApp')->plainTextToken;
        $success['name'] =  $user->name;

        return $this->sendResponse($success, 'User register successfully.');
    }

    public function listCity(): JsonResponse
    {
        $cities = City::withCount([
            'origins as origins_count',
            'destinations as destinations_count',
            // distinct customers count via destinations
            'customers as customers_count' => function ($query) {
                $query->select(DB::raw('COUNT(DISTINCT customers.id)'));
            },
        ])->get();

        return $this->sendResponse($cities, 'City retrieved successfully');
    }

    public function listPhases(): JsonResponse
    {
        $cities = DeliveryPhase::get();

        return $this->sendResponse($cities, 'Phase Data');
    }

    public function routeVoucher(): JsonResponse
    {
        $routeVoucher = RouteVoucher::with(['origin', 'destination'])->get();

        $routeVoucher->transform(function ($delivery) {
            $delivery->count_vendor = RoutesVoucherUssage::where('category', 'vendor')
                ->where('voucher_id', $delivery->id)
                ->distinct('reference_id')
                ->count('reference_id');

            $delivery->count_customer = RoutesVoucherUssage::where('category', 'customer')
                ->where('voucher_id', $delivery->id)
                ->distinct('reference_id')
                ->count('reference_id');

            return $delivery;
        });

        return $this->sendResponse($routeVoucher, "Success");
    }


    public function routeVoucherByID($id): JsonResponse
    {
        $routeVoucher = RouteVoucher::with(['origin', 'destination'])->find($id);

        if (!$routeVoucher) {
            return $this->sendError("Route voucher not found", 404);
        }

        $usages = RoutesVoucherUssage::where('voucher_id', $id)->get();

        $routeVoucher->vendor = $usages->where('category', 'vendor')
            ->map(function ($usage) {
                $usage->vendor = Vendor::where('id', $usage->reference_id)
                    ->where('type', 'Unit')
                    ->first();
                return $usage;
            })
            ->filter(function ($usage) {
                return !is_null($usage->vendor);
            })
            ->values();

        $routeVoucher->customer = $usages->where('category', 'customer')->map(function ($usage) {
            $usage->customer = Customer::find($usage->reference_id);
            return $usage;
        })->values();

        return $this->sendResponse($routeVoucher, "Success");
    }

    public function addRouteVoucher(Request $request)
    {
        $input = $request->all();
        $result = [
            "voucher_code" => $input['voucher_code'],
            "origin_city" => $input['origin'],
            "destination_city" => $input['destination'],
            "duration_display" => $input['duration_display'],
            "duration_value" => $input['duration_value'],
            "distance_display" => $input['distance_display'],
            "distance_value" => $input['distance_value'],
            'created_by' => auth()->id()
        ];

        RouteVoucher::create($result);
        return $this->sendResponse([], 'User register successfully.');
    }

    public function getAllData()
    {
        $routeVoucher = Destination::with(['city', 'origin', 'origin.city'])
            ->where('customer_id', 5)
            ->get();

        return $this->sendResponse($routeVoucher, "Success");
    }

    public function getCustomerExpenseParam($id)
    {
        return $this->sendResponse(customerExpenseParam::where('customer_id', $id)->get(), "Success");
    }

    public function mappingSLA()
    {
        $destination = Destination::with(['origin.city', 'city'])->get();

        foreach ($destination as $cities) {
            if (!$cities->SLA || !$cities->KM) {
                $result = $this->getDistanceAndETA($cities->city->lat . ',' . $cities->city->lng, $cities->origin->city->lat . ',' . $cities->origin->city->lng);

                if ($result) {
                    $cities->SLA = $result['duration_value'];
                    $cities->KM = $result['distance_value'];
                    $cities->save();
                }
            }
        }
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
            throw new Exception('Request Error: ' . curl_error($ch));
        }

        curl_close($ch);
        $data = json_decode($response, true);

        if ($data['rows'][0]['elements'][0]['status'] === 'OK') {
            $distance = $data['rows'][0]['elements'][0]['distance']['value'] + ($data['rows'][0]['elements'][0]['distance']['value'] * 0.1);
            $distance_cal = ceil($distance / 1000);
            $sla_value = ceil(ceil($distance_cal / 20) / 24);
            $sla_minutes = $sla_value * 24 * 60;

            return [
                'distance' => $distance_cal . ' km',
                'distance_value' => $distance_cal,
                'duration_value' => $sla_minutes,
                'duration' => $sla_minutes . ' min(s)'
            ];
        }

        return false;
    }

    public function storeCp(Request $request)
    {
        $create = DeliveryPicture::create([
            'type'         => $request->type,
            'ref_id'       => $request->ref_id,
            'file_name'    => $request->file_name,
            'file_path'    => null,
            'created_date' => now(),
            'created_by'   => $request->created_by,
        ]);

        $filepath = $this->storeBase64($request->base64, $request->file_name);

        $create->update([
            'file_path' => $filepath,
        ]);

        return response()->json(['message' => 'Saved']);
    }

    public function storeWaybill(Request $request)
    {
        $create = DeliveryWaybill::create([
            'delivery_id'   => $request->delivery_id,
            'type'          => $request->type,
            'number'        => $request->number,
            'quantity'      => $request->quantity,
            'units'         => $request->units,
            'remarks'       => $request->remarks,
            'status'        => 'Draft',
            'file_name'     => $request->file_name,
            'file_path'     => null,
            'created_date'  => now(),
            'created_by'    => auth()->id()
        ]);

        $filepath = $this->storeBase64($request->base64, $request->file_name);

        $create->update([
            'file_path' => $filepath,
        ]);

        return response()->json(['message' => 'Saved']);
    }

    public function uploadWaybill(Request $request)
    {
        $create = DeliveryWaybillUpload::create([
            'delivery_id'           => $request->delivery_id,
            'driver_id'             => $request->driver_id,
            'upload_date'           => $request->upload_date,
            'file_name'             => $request->file_name,
            'delivery_phase_id'     => $request->delivery_phase_id,
            'file_path'             => null,
            'created_at'            => now(),
            'created_by'            => auth()->id()
        ]);

        $filepath = $this->storeBase64($request->base64, $request->file_name);

        $create->update([
            'file_path' => $filepath,
        ]);

        return response()->json(['message' => 'Saved']);
    }

    public function storeWaybillExpedition(Request $request)
    {
        $create = DeliveryWaybillExpedition::create([
            'delivery_id'       => $request->delivery_id,
            'courier'           => $request->courier,
            'tracking_number'   => $request->tracking_number,
            'sent_by'           => $request->sent_by,
            'sent_date'         => $request->sent_date,
            'remarks'           => $request->remarks,
            'created_date'      => now(),
            'created_by'        => auth()->id()
        ]);

        DeliveryWaybillTracker::create([
            'delivery_waybill_expedition_id'   => $create->id,
            'status'                            => "In Delivery Courier",
            'remarks'                           => "Tracking Number " . $request->tracking_number,
            'created_date'                      => now(),
            'created_by'                        => auth()->id()
        ]);

        DeliveryWaybill::where('delivery_id', $request->delivery_id)
            ->update(['status' => 'In Progress']);

        return response()->json(['message' => 'Saved']);
    }

    public function storeWaybillTracker(Request $request)
    {
        DeliveryWaybillTracker::create([
            'delivery_waybill_expedition_id'   => $request->delivery_waybill_expedition_id,
            'status'                            => $request->status,
            'remarks'                           => $request->remarks,
            'created_date'                      => now(),
            'created_by'                        => auth()->id()
        ]);

        if ($request->status == 'Received by Operation') {
            DeliveryWaybill::where('delivery_id', $request->delivery_id)
                ->update(['status' => 'Final']);
        }

        return response()->json(['message' => 'Saved']);
    }

    public function finalizeWaybill(Request $request)
    {
        DeliveryWaybill::where('delivery_id', $request->delivery_id)
            ->update(['status' => 'Final']);

        return response()->json(['message' => 'Saved']);
    }

    public function updateCp(Request $request, $id)
    {
        $checkpoint = WorkOrderCheckpoint::find($id);

        if (!$checkpoint) {
            return response()->json(['message' => 'Checkpoint not found'], 404);
        }

        $filepath = $this->storeBase64($request->base64, $request->file_name);
        $checkpoint->file_name          = $request->file_name;
        $checkpoint->file_path          = $filepath;
        $checkpoint->notes              = $request->notes;
        $checkpoint->checkpoint_date    = now();
        $checkpoint->status             = 'Submitted';

        $checkpoint->save();

        return response()->json([
            'message' => 'Checkpoint updated successfully',
            'data'    => $checkpoint
        ]);
    }

    public function storeReport(Request $request)
    {
        $create = WorkOrderReport::create([
            'work_order_id'   => $request->work_order_id,
            'report_details'  => $request->report_details,
            'report_date'     => $request->report_date,
            'reported_by'     => auth()->id(),
            'status'          => 'Pending',
            'created_by'      => auth()->id(),
            'created_at'      => now(),
        ]);

        $filepath = $this->storeBase64($request->base64, $request->file_name);

        WorkOrderReportPicture::create([
            'work_order_report_id' => $create->id,
            'file_name'            => $request->file_name,
            'file_path'            => $filepath,
            'created_by'           => auth()->id(),
            'created_at'           => now(),
        ]);

        return response()->json(['message' => 'Saved']);
    }


    public function updateReport(Request $request)
    {
        $create = WorkOrderReport::where('id', $request->id)->update([
            'resolution_notes'   => $request->resolution_notes,
            'resolved_by'        => auth()->id(),
            'resolved_at'        => now(),
            'status'             => 'Completed',
        ]);

        return response()->json(['message' => 'Saved']);
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

    public function download($filename)
    {
        $path = 'uploads/' . $filename;

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File not found.');
        }

        $fullPath = Storage::disk('public')->path($path);
        return response()->download($fullPath);
    }
}
