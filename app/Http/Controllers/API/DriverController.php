<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\AccountsDocUpload;
use App\Models\CityGroupMapping;
use App\Models\Driver;
use App\Models\DriverGroupMapping;
use App\Models\DriverUnitAssignment;
use App\Models\RoutesVoucherUssage;
use Illuminate\Http\Request;

class DriverController extends BaseController
{
    // 🚦 List all drivers
    public function index()
    {
        $drivers = Driver::with(['vendor', 'creator', 'updater'])->get();
        $drivers->map(function ($driver) {
            $driver->personal_docs = AccountsDocUpload::where(['ref_type' => 'driver_personal', 'ref_id' => $driver->id,])->get()->map(function ($doc) {
                $doc->url = asset(ltrim($doc->file_path, '/'));
                return $doc;
            });
            return $driver;
        });
        return response()->json(['message' => 'All drivers retrieved successfully.', 'data' => $drivers,]);
    }

    public function getAllVendor()
    {
        $drivers = Driver::where('ownership', 'rented')->with(['vendor', 'creator', 'updater'])->get();

        return response()->json([
            'message' => 'All drivers retrieved successfully.',
            'data' => $drivers,
        ]);
    }

    public function getAllOwned()
    {
        $drivers = Driver::where('ownership', 'owned')->with(['vendor', 'creator', 'updater'])->get();

        return response()->json([
            'message' => 'All drivers retrieved successfully.',
            'data' => $drivers,
        ]);
    }

    // 🔍 Show one driver
    public function show($id)
    {
        $driver = Driver::with(['vendor', 'creator', 'updater'])->findOrFail($id);

        return response()->json([
            'message' => 'Driver details retrieved.',
            'data' => $driver,
        ]);
    }

    // 🆕 Create a driver
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'ownership' => 'required|in:owned,rented',
            'status' => 'in:active,inactive',
            'vendor_id' => 'nullable|exists:vendors,id',
            'phone' => 'nullable|string|max:191',
            'birth_date' => 'nullable|date',
            // Add other fields as needed
        ]);

        $validated['created_by'] = auth()->id();

        $driver = Driver::create($validated);

        return response()->json([
            'message' => 'Driver created successfully.',
            'data' => $driver,
        ]);
    }

    // ✏️ Update a driver
    public function update(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);

        $validated = $request->validate([
            'ownership' => 'in:owned,rented',
            'status' => 'in:active,inactive',
            'phone' => 'nullable|string|max:191',
            'birth_date' => 'nullable|date',
            // Add other fields as needed
        ]);

        $validated['updated_by'] = auth()->id();

        $driver->update($validated);

        return response()->json([
            'message' => 'Driver updated successfully.',
            'data' => $driver,
        ]);
    }

    // 🗑️ Delete a driver
    public function destroy($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->delete();

        return response()->json([
            'message' => 'Driver deleted successfully.',
        ]);
    }

    public function availableUnit($type)
    {
        if ($type == 'all') {
            return $this->sendResponse(
                DriverUnitAssignment::with(['unit.vendor', 'driver'])
                    ->get(),
                "Success"
            );
        }
        return $this->sendResponse(
            DriverUnitAssignment::with(['unit.vendor', 'driver'])
                ->whereHas('unit', function ($query) use ($type) {
                    $query->where('ownership', $type);
                })
                ->get(),
            "Success"
        );
    }

    public function unitByVendor($id)
    {
        return $this->sendResponse(
            DriverUnitAssignment::with(['unit.vendor', 'driver'])
                ->whereHas('unit', function ($query) use ($id) {
                    $query->where('vendor_id', $id);
                })
                ->get(),
            "Success"
        );
    }

    public function getDriverAvailability($id, $voucher_id)
    {
        $getOriginData = CityGroupMapping::where('city_id', $id)->firstOrFail();

        $getUnitsGroup = DriverGroupMapping::with('driver.vendor')
            //->where('group_id', $getOriginData->group_id)
            ->get();

        $units = $getUnitsGroup->pluck('driver');

        $vendors = $units
            ->pluck('vendor')
            //->filter(fn ($vendor) => $vendor->id != 1)
            ->unique('id')
            ->values();

        $owned = $units
            ->where('ownership', 'owned')
            ->values();

        $owned->map(function ($driver) {
            $driver->personal_docs = AccountsDocUpload::where(['ref_type' => 'driver_personal', 'ref_id' => $driver->id,])->get()->map(function ($doc) {
                $doc->url = asset(ltrim($doc->file_path, '/'));
                return $doc;
            });
            return $driver;
        });

        $rented = $units
            ->where('ownership', 'rented')
            ->values();

        // $vendors = $vendors->map(function ($vendor) use ($rented, $voucher_id) {
        //     $getPricing = RoutesVoucherUssage::where([
        //         'category'     => 'vendor',
        //         'reference_id' => $vendor->id,
        //         'voucher_id'   => $voucher_id
        //     ])->first();

        //     return [
        //         'amount' => $getPricing ? $getPricing->amount : 0,
        //         'id'     => $vendor->id,
        //         'name'   => $vendor->name,
        //         'drivers'  => $rented
        //             ->filter(fn ($e) => $e->vendor_id == $vendor->id)
        //             ->values(),
        //     ];
        // });

        return $this->sendResponse(
            ['owned' => $owned, 'vendors' => $vendors],
            "Success"
        );
    }
}
