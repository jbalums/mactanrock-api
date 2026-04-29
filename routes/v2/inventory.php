<?php

use App\Http\Controllers\Api\V2\InventoryController;
use App\Http\Controllers\Api\V2\InventoryTransactionHistoriesController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->group(function () {
    Route::get('/', [InventoryController::class, 'index']);
    Route::get('summary', [InventoryController::class, 'summary']);
    Route::get('transaction-histories', InventoryTransactionHistoriesController::class);
    Route::get('{inventory}', [InventoryController::class, 'show'])->whereNumber('inventory');
});
