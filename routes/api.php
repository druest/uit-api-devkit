<?php

use App\Http\Controllers\API\CustomerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\DeliveryController;
use App\Http\Controllers\API\WorkOrderController;
use App\Http\Controllers\API\DriverController;
use App\Http\Controllers\API\ExpenseController;
use App\Http\Controllers\API\UnitController;

Route::controller(RegisterController::class)->group(function () {
    Route::post('register', 'register')->name('register');
    Route::post('login', 'login')->name('login');
});

Route::middleware('auth:sanctum')->group(function () {
    //delivery api resources
    Route::get('delivery/getOutstandingWorkOrder', [DeliveryController::class, 'getOutstandingWorkOrder']);
    Route::get('delivery/{id}/selectRoutesById', [DeliveryController::class, 'selectRoutesById']);
    Route::post('delivery/insertDeliveryOrder', [DeliveryController::class, 'insertDeliveryOrder']);
    Route::get('delivery/deliveryTypes', [DeliveryController::class, 'deliveryTypes']);
    Route::get('delivery/{id}/routes', [DeliveryController::class, 'routes']);
    Route::get('delivery/{id}/origins', [DeliveryController::class, 'origins']);
    Route::get('delivery/{id}/destinations', [DeliveryController::class, 'destinations']);

    //workOrder api resources
    Route::get('workorder/{id}/getByStatus', [WorkOrderController::class, 'getByStatus']);
    Route::get('workorder/getListOtherExpense', [WorkOrderController::class, 'getListOtherExpense']);
    Route::post('workorder/storeWorkOrderOtherExpense', [WorkOrderController::class, 'storeWorkOrderOtherExpense']);
    Route::get('workorder/{id}/getOutstandingOtherExpense', [WorkOrderController::class, 'getOutstandingOtherExpense']);
    Route::get('workorder/{id}/getTripPackageByDestinationID', [WorkOrderController::class, 'getTripPackageByDestinationID']);

    //unit api resources
    Route::get('unit/availableUnit', [UnitController::class, 'availableUnit']);

    //unit api resources
    Route::get('expense/getOutstandingWOPayment', [ExpenseController::class, 'getOutstandingWOPayment']);
    Route::get('expense/getCompanyAccounts', [ExpenseController::class, 'getCompanyAccounts']);

    //base api resources
    Route::apiResource('workorder', WorkOrderController::class);
    Route::apiResource('unit', UnitController::class);
    Route::apiResource('driver', DriverController::class);
    Route::apiResource('delivery', DeliveryController::class);
    Route::apiResource('customer', CustomerController::class);

    //auth api resources
    Route::get('user', function (Request $request) {
        return $request->user();
    })->name('user');
});
