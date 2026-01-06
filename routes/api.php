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

// RegisterController routes
Route::controller(RegisterController::class)->group(function () {
    Route::post('register', 'register')->name('register');
    Route::post('login', 'login')->name('login');
    Route::post('login/driver', 'driverLogin')->name('driver.login');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('delivery/getSummaryAP', [DeliveryController::class, 'getSummaryAP']);
    Route::get('delivery/getSummaryAR', [DeliveryController::class, 'getSummaryAR']);
    Route::get('delivery/generateCityData', [DeliveryController::class, 'generateCityData']);
    Route::get('delivery/generateRouteData', [DeliveryController::class, 'generateRouteData']);

    Route::get('delivery/getDeliveryTracker', [DeliveryController::class, 'getDeliveryTracker']);
    Route::get('delivery/getOutStandingWaybill', [DeliveryController::class, 'getOutStandingWaybill']);
    Route::get('delivery/getOutstandingWorkOrder', [DeliveryController::class, 'getOutstandingWorkOrder']);
    Route::get('delivery/getOutstandingSubcontract', [DeliveryController::class, 'getOutstandingSubcontract']);
    Route::get('delivery/getOutStandingPlan', [DeliveryController::class, 'getOutStandingPlan']);
    Route::get('delivery/{id}/selectRoutesById', [DeliveryController::class, 'selectRoutesById']);
    Route::post('delivery/insertDeliveryOrder', [DeliveryController::class, 'insertDeliveryOrder']);
    Route::post('delivery/storeDeliveryPlan', [DeliveryController::class, 'storeDeliveryPlan']);
    Route::post('delivery/updateSCSDate', [DeliveryController::class, 'updateSCSDate']);
    Route::post('delivery/updateDCSDate', [DeliveryController::class, 'updateDCSDate']);
    Route::post('delivery/updateFleetData', [DeliveryController::class, 'updateFleetData']);
    Route::get('delivery/deliveryTypes', [DeliveryController::class, 'deliveryTypes']);
    Route::get('delivery/{id}/routes', [DeliveryController::class, 'routes']);

    Route::get('delivery/{id}/showForWaybill', [DeliveryController::class, 'showForWaybill']);
    Route::get('delivery/{id}/showForWO', [DeliveryController::class, 'showForWO']);
    Route::get('delivery/{id}/showForDI', [DeliveryController::class, 'showForDI']);
    Route::get('delivery/{id}/getByCustomer', [DeliveryController::class, 'getByCustomer']);
    Route::get('delivery/{id}/getRouteByCustomer', [DeliveryController::class, 'getRouteByCustomer']);
    Route::get('delivery/{id}/deliveryType', [DeliveryController::class, 'deliveryType']);
    Route::get('delivery/{id}/origins', [DeliveryController::class, 'origins']);
    Route::get('delivery/{id}/destinations', [DeliveryController::class, 'destinations']);
    Route::get('delivery/{id}/{typeID}/{customerID}/getDestinationPriceById', [DeliveryController::class, 'getDestinationPriceById']);
    //getReports
    //storeOtherDI
    //selectOtherDI
    //getAllOtherDI
    //submitAcceptance
    //getDeliveryByUserID
    Route::get('workorder/getDeliveryByUserID', [WorkOrderController::class, 'getDeliveryByUserID']);
    Route::post('workorder/submitAcceptance', [WorkOrderController::class, 'submitAcceptance']);
    Route::get('workorder/getOutstandingOtherDI', [WorkOrderController::class, 'getOutstandingOtherDI']);
    Route::get('workorder/getAllOtherDI', [WorkOrderController::class, 'getAllOtherDI']);
    Route::get('workorder/{id}/selectOtherDI', [WorkOrderController::class, 'selectOtherDI']);
    Route::post('workorder/storeOtherDI', [WorkOrderController::class, 'storeOtherDI']);
    Route::get('workorder/getReports', [WorkOrderController::class, 'getReports']);
    Route::get('workorder/{id}/getUJValues', [WorkOrderController::class, 'getUJValues']);
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
    Route::get('workorder/{id}/processUJ', [WorkOrderController::class, 'processUJ']);
    //processUJ
    //getPlanningData
    Route::get('unit/{start_date}/{end_date}/getPlanningData', [UnitController::class, 'getPlanningData']);
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
    //generateInvoice
    Route::get('expense/generateInvoice', [ExpenseController::class, 'generateInvoice']);
    Route::get('expense/getOutstandingWOPayment', [ExpenseController::class, 'getOutstandingWOPayment']);
    Route::get('expense/getCompanyAccounts', [ExpenseController::class, 'getCompanyAccounts']);
    Route::post('expense/submitExpense', [ExpenseController::class, 'submitExpense']);
    //submitExpense
    //lookup
    //listPhases
    Route::get('lookup/listPhases', [LookupController::class, 'listPhases']);
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
    //uploadWaybill
    Route::post('lookup/uploadWaybill', [LookupController::class, 'uploadWaybill']);
    Route::post('lookup/storeWaybill', [LookupController::class, 'storeWaybill']);
    Route::post('lookup/storeWaybillExpedition', [LookupController::class, 'storeWaybillExpedition']);
    Route::post('lookup/storeWaybillTracker', [LookupController::class, 'storeWaybillTracker']);
    Route::post('lookup/finalizeWaybill', [LookupController::class, 'finalizeWaybill']);
    Route::post('lookup/upload-base64', [LookupController::class, 'storeBase64']);
    Route::post('lookup/storeCp', [LookupController::class, 'storeCp']);
    //updateReport
    Route::post('lookup/updateReport', [LookupController::class, 'updateReport']);
    Route::post('lookup/storeReport', [LookupController::class, 'storeReport']);
    Route::put('lookup/updateCp/{id}', [LookupController::class, 'updateCp']);
    //storeReport
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
