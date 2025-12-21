<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DriverUnitAssignment;
use App\Models\RoutesVoucherUssage;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends BaseController
{
    // 🚚 List all Vendor
    public function index()
    {
        $units = Vendor::with(['creator', 'updater'])->get();

        return response()->json([
            'message' => 'All vendor retrieved successfully.',
            'data' => $units,
        ]);
    }

    public function show($id)
    {
        $vendor = Vendor::with([
            'units.unitGroupMappings.group',
            'driver.driverGroupMappings.group',
            'voucherUsages.voucher.origin',
            'voucherUsages.voucher.destination'
        ])->findOrFail($id);

        return $this->sendResponse($vendor, "Success");
    }

    public function findVendorByRoute($id, $ref_type)
    {
        $getVendors = RoutesVoucherUssage::where(['voucher_id' => $id, 'category' => 'vendor', 'reference_type' => $ref_type])->get();

        foreach ($getVendors as $usage) {
            $usage->vendor_data = Vendor::find($usage->reference_id);
        }

        return $this->sendResponse($getVendors, "Success");
    }
}
