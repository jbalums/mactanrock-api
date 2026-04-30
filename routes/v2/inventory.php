<?php

use App\Http\Controllers\Api\V2\InventoryController;
use App\Http\Controllers\Api\V2\InventoryTransactionHistoriesController;
use App\Http\Controllers\Api\V2\RequisitionController;
use App\Http\Controllers\Api\V2\RequisitionDiscrepancyController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->group(function () {
    Route::get('/', [InventoryController::class, 'index']);
    Route::get('requisition-discrepancies', RequisitionDiscrepancyController::class);
    Route::get('requisition', [RequisitionController::class, 'index']);
    Route::get('summary', [InventoryController::class, 'summary']);
    Route::get('transaction-histories', InventoryTransactionHistoriesController::class);
    Route::get('{inventory}', [InventoryController::class, 'show'])->whereNumber('inventory');
});
