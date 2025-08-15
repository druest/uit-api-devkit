<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends BaseController
{
    // 🚦 List all drivers
    public function index()
    {
        $drivers = Driver::with(['vendor', 'creator', 'updater'])->get();

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
}
