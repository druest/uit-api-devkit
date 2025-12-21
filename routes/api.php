<?php

use App\Http\Controllers\API\AccountController;
use App\Http\Controllers\API\CustomerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\DeliveryController;
use App\Http\Controllers\API\WorkOrderController;
use App\Http\Controllers\API\DriverController;
use App\Http\Controllers\API\ExpenseController;
use App\Http\Controllers\API\LookupController;
use App\Http\Controllers\API\UnitController;
use App\Http\Controllers\API\VendorController;

Route::controller(RegisterController::class)->group(function () {
    Route::post('register', 'register')->name('register');
    Route::post('login', 'login')->name('login');
});

Route::middleware('auth:sanctum')->group(function () {
    //delivery api resources
    //getSummaryAR
    //getSummaryAP
    Route::get('delivery/getSummaryAP', [DeliveryController::class, 'getSummaryAP']);
    Route::get('delivery/getSummaryAR', [DeliveryController::class, 'getSummaryAR']);
    Route::get('delivery/generateCityData', [DeliveryController::class, 'generateCityData']);
    Route::get('delivery/generateRouteData', [DeliveryController::class, 'generateRouteData']);
    //generateRouteData
    Route::get('delivery/getOutstandingWorkOrder', [DeliveryController::class, 'getOutstandingWorkOrder']);
    Route::get('delivery/getOutstandingSubcontract', [DeliveryController::class, 'getOutstandingSubcontract']);
    Route::get('delivery/getOutStandingPlan', [DeliveryController::class, 'getOutStandingPlan']);
    Route::get('delivery/{id}/selectRoutesById', [DeliveryController::class, 'selectRoutesById']);
    Route::post('delivery/insertDeliveryOrder', [DeliveryController::class, 'insertDeliveryOrder']);
    Route::post('delivery/storeDeliveryPlan', [DeliveryController::class, 'storeDeliveryPlan']);
    Route::post('delivery/updateSCSDate', [DeliveryController::class, 'updateSCSDate']);
    Route::post('delivery/updateDCSDate', [DeliveryController::class, 'updateDCSDate']);
    Route::post('delivery/updateFleetData', [DeliveryController::class, 'updateFleetData']);
    //updateFleetData
    Route::get('delivery/deliveryTypes', [DeliveryController::class, 'deliveryTypes']);
    Route::get('delivery/{id}/routes', [DeliveryController::class, 'routes']);
    Route::get('delivery/{id}/showForWO', [DeliveryController::class, 'showForWO']);
    Route::get('delivery/{id}/showForDI', [DeliveryController::class, 'showForDI']);
    Route::get('delivery/{id}/getByCustomer', [DeliveryController::class, 'getByCustomer']);
    //deliveryType
    //getByCustomer
    //getRouteByCustomer
    Route::get('delivery/{id}/getRouteByCustomer', [DeliveryController::class, 'getRouteByCustomer']);
    Route::get('delivery/{id}/deliveryType', [DeliveryController::class, 'deliveryType']);
    Route::get('delivery/{id}/origins', [DeliveryController::class, 'origins']);
    Route::get('delivery/{id}/destinations', [DeliveryController::class, 'destinations']);
    Route::get('delivery/{id}/{typeID}/{customerID}/getDestinationPriceById', [DeliveryController::class, 'getDestinationPriceById']);

    Route::post('workorder/storeDeliveryProblem', [WorkOrderController::class, 'storeDeliveryProblem']);
    Route::post('workorder/storeDriverAction', [WorkOrderController::class, 'storeDriverAction']);
    Route::post('workorder/storeExternalWO', [WorkOrderController::class, 'storeExternalWO']);
    Route::get('workorder/{id}/getCalculatedeExpense', [WorkOrderController::class, 'getCalculatedeExpense']);
    Route::get('workorder/{id}/getByStatus', [WorkOrderController::class, 'getByStatus']);
    Route::get('workorder/getListOtherExpense', [WorkOrderController::class, 'getListOtherExpense']);
    Route::get('workorder/fleetMonitoringView', [WorkOrderController::class, 'fleetMonitoringView']);
    Route::get('workorder/scsView', [WorkOrderController::class, 'scsView']);
    Route::get('workorder/dcsView', [WorkOrderController::class, 'dcsView']);
    Route::get('workorder/reconcile', [WorkOrderController::class, 'reconcile']);
    Route::post('workorder/storeWorkOrderOtherExpense', [WorkOrderController::class, 'storeWorkOrderOtherExpense']);
    Route::post('workorder/storeUpdatedExternalWO', [WorkOrderController::class, 'storeUpdatedExternalWO']);
    Route::get('workorder/{id}/getOutstandingOtherExpense', [WorkOrderController::class, 'getOutstandingOtherExpense']);
    Route::get('workorder/{id}/getTripPackageByDestinationID', [WorkOrderController::class, 'getTripPackageByDestinationID']);
    Route::get('unit/{id}/{voucher_id}/getUnitAvailability', [UnitController::class, 'getUnitAvailability']);
    Route::get('unit/{id}/unitByVendor', [UnitController::class, 'unitByVendor']);
    Route::get('unit/{type}/availableUnit', [UnitController::class, 'availableUnit']);

    // /getAllVendor
    //getDriverAvailability
    Route::get('driver/{id}/{voucher_id}/getDriverAvailability', [DriverController::class, 'getDriverAvailability']);
    Route::get('driver/getAllVendor', [DriverController::class, 'getAllVendor']);
    Route::get('driver/getAllOwned', [DriverController::class, 'getAllOwned']);
    //getAllOwned
    //unit api resources
    Route::get('expense/getOutstandingWOPayment', [ExpenseController::class, 'getOutstandingWOPayment']);
    Route::get('expense/getCompanyAccounts', [ExpenseController::class, 'getCompanyAccounts']);

    //lookup
    Route::get('lookup/getAllData', [LookupController::class, 'getAllData']);
    Route::get('lookup/{id}/routeVoucherByID', [LookupController::class, 'routeVoucherByID']);
    Route::get('lookup/{id}/getCustomerExpenseParam', [LookupController::class, 'getCustomerExpenseParam']);
    Route::get('lookup/{origin}/{destination}/getDistanceAndETA', [LookupController::class, 'getDistanceAndETA']);
    Route::get('lookup/listCity', [LookupController::class, 'listCity']);
    Route::post('lookup/addRouteVoucher', [LookupController::class, 'addRouteVoucher']);
    Route::get('lookup/routeVoucher', [LookupController::class, 'routeVoucher']);
    Route::get('lookup/mappingSLA', [LookupController::class, 'mappingSLA']);
    Route::get('lookup/download/{filename}', [LookupController::class, 'download']);
    //storeCp
    Route::post('lookup/upload-base64', [LookupController::class, 'storeBase64']);
    Route::post('lookup/storeCp', [LookupController::class, 'storeCp']);
    //mappingSLA

    //accounts
    Route::post('database/storeDriver', [AccountController::class, 'storeDriver']);
    Route::get('database/{id}/getDriver', [AccountController::class, 'getDriver']);


    //vendpr

    Route::get('vendor/{id}/{ref_type}/findVendorByRoute', [VendorController::class, 'findVendorByRoute']);
    //routeVoucher
    //base api resources
    Route::apiResource('workorder', WorkOrderController::class);
    Route::apiResource('unit', UnitController::class);
    Route::apiResource('vendor', VendorController::class);
    Route::apiResource('driver', DriverController::class);
    Route::apiResource('delivery', DeliveryController::class);
    Route::apiResource('customer', CustomerController::class);

    //auth api resources
    Route::get('user', function (Request $request) {
        return $request->user();
    })->name('user');
});
