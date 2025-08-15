<?php

namespace App\Http\Controllers\API;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends BaseController
{
    public function index()
    {
        return Customer::with(['creator', 'updater'])->paginate(20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:customers',
            'email' => 'required|email|unique:customers',
            'tax_id_number' => 'required|string|unique:customers',
            'address' => 'required|string',
            'payment_due_date' => 'nullable|date',
            'description' => 'nullable|string',
            'is_taxable' => 'boolean',
            'requires_final_tax' => 'boolean',
            'register_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = Auth::id();

        $customer = Customer::create($validated);

        return response()->json($customer->load(['creator']), 201);
    }

    public function show($id)
    {
        return Customer::with(['creator', 'updater'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|unique:customers,code,' . $id,
            'email' => 'sometimes|email|unique:customers,email,' . $id,
            'tax_id_number' => 'sometimes|string|unique:customers,tax_id_number,' . $id,
            'address' => 'sometimes|string',
            'payment_due_date' => 'nullable|date',
            'description' => 'nullable|string',
            'is_taxable' => 'boolean',
            'requires_final_tax' => 'boolean',
            'register_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = Auth::id();

        $customer->update($validated);

        return response()->json($customer->load(['updater']));
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return response()->json(['message' => 'Customer deleted']);
    }
}
