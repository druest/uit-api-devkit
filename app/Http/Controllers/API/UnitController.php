<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CityGroupMapping;
use App\Models\DeliveryPlan;
use App\Models\DriverUnitAssignment;
use App\Models\RoutesVoucherUssage;
use App\Models\RouteVoucher;
use App\Models\Unit;
use App\Models\UnitGroupMapping;
use Illuminate\Http\Request;

class UnitController extends BaseController
{
    // 🚚 List all units
    public function index()
    {
        $units = Unit::with(['vendor', 'creator', 'updater'])->get();

        return response()->json([
            'message' => 'All units retrieved successfully.',
            'data' => $units,
        ]);
    }

    // 🔍 Show one unit
    public function show($id)
    {
        $unit = Unit::with(['vendor', 'creator', 'updater'])->findOrFail($id);

        return response()->json([
            'message' => 'Unit details retrieved.',
            'data' => $unit,
        ]);
    }

    // 🆕 Create a unit
    public function store(Request $request)
    {
        $validated = $request->validate([
            'plate_full' => 'required|string|max:191',
            'plate_region' => 'required|string|max:191',
            'plate_number' => 'required|string|max:191',
            'plate_suffix' => 'required|string|max:191',
            'ownership' => 'required|in:owned,rented',
            'vendor_id' => 'required|exists:vendors,id',
            'is_active' => 'boolean',
            // Add other fields as needed
        ]);

        $validated['created_by'] = auth()->id();

        $unit = Unit::create($validated);

        return response()->json([
            'message' => 'Unit created successfully.',
            'data' => $unit,
        ]);
    }

    // ✏️ Update a unit
    public function update(Request $request, $id)
    {
        $unit = Unit::findOrFail($id);

        $validated = $request->validate([
            'ownership' => 'in:owned,rented',
            'is_active' => 'boolean',
            // Add other fields as needed
        ]);

        $validated['updated_by'] = auth()->id();

        $unit->update($validated);

        return response()->json([
            'message' => 'Unit updated successfully.',
            'data' => $unit,
        ]);
    }

    // 🗑️ Delete a unit
    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);
        $unit->delete();

        return response()->json([
            'message' => 'Unit deleted successfully.',
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

    public function getUnitAvailability($id, $voucher_id)
    {
        //$getUnavailUnit = DeliveryPlan::where('')->
        $getOriginData = CityGroupMapping::where('city_id', $id)->firstOrFail();

        $getUnitsGroup = UnitGroupMapping::with('unit.vendor')
            ->where('group_id', $getOriginData->group_id)
            ->get();

        $units = $getUnitsGroup->pluck('unit');

        $vendors = $units
            ->pluck('vendor')
            ->filter(fn ($vendor) => $vendor->id != 1)
            ->unique('id')
            ->values();

        $owned = $units
            ->where('ownership', 'owned')
            ->values();

        $rented = $units
            ->where('ownership', 'rented')
            ->values();

        $vendors = $vendors->map(function ($vendor) use ($rented, $voucher_id) {
            $getPricing = RoutesVoucherUssage::where([
                'category'     => 'vendor',
                'reference_id' => $vendor->id,
                'voucher_id'   => $voucher_id
            ])->first();

            return [
                'amount' => $getPricing ? $getPricing->amount : 0,
                'id'     => $vendor->id,
                'name'   => $vendor->name,
                'units'  => $rented
                    ->filter(fn ($e) => $e->vendor_id == $vendor->id)
                    ->values(),
            ];
        });

        return $this->sendResponse(
            ['owned' => $owned, 'vendors' => $vendors],
            "Success"
        );
    }
}
