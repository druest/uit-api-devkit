<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DriverUnitAssignment;
use App\Models\Unit;
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

    public function availableUnit()
    {
        return $this->sendResponse(DriverUnitAssignment::with(['unit.vendor', 'driver'])->get(), "Success");
    }
}
