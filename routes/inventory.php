<?php

use App\Http\Controllers\ApprovingManager\RequisitionsController;
use App\Http\Controllers\Inventory\AcceptingStatsController;
use App\Http\Controllers\Inventory\ApprovingRequisitionController;
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\Inventory\ReceivingCompleteController;
use App\Http\Controllers\Inventory\ProjectPlantOrdersController;
use App\Http\Controllers\Inventory\ReceivingController;
use App\Http\Controllers\Inventory\RequestController;
use App\Http\Controllers\Inventory\RequisitionController;
use App\Http\Controllers\Inventory\TriggersController;
use Illuminate\Support\Facades\Route;

Route::get('receiving', [ReceivingController::class, 'index']);
Route::get('receiving/{id}', [ReceivingController::class, 'show']);
Route::post('receiving', [ReceivingController::class, 'store']);
Route::patch('receiving-complete', [ReceivingCompleteController::class, 'update']);

Route::get('requisition', [RequisitionController::class, 'index']);
Route::get('request', [RequestController::class, 'index']);
Route::get('request/{id}', [RequestController::class, 'show']);
Route::post('requisition', [RequisitionController::class, 'store']);
Route::post('requisition-approved/{id}', [ApprovingRequisitionController::class, 'update']);
Route::get('requisition/{id}', [RequisitionController::class, 'show']);
Route::post('requisition-accept/{id}', [RequisitionController::class, 'accept']);

Route::post('approve-issuance/{id}', [RequisitionController::class, 'approveIssuance']);

Route::get('issuances', [RequisitionController::class, 'issuances']);
Route::get('approval-issuances', [RequisitionController::class, 'issuancesForApproval']);

Route::get('get-receiving-issuances', [RequisitionController::class, 'receivingIssuances']);

Route::post('issuances/{id}', [RequisitionController::class, 'createIssuances']);
Route::post('issuances-recieved/{id}', [RequisitionController::class, 'receivedIssuances']);

Route::get('project-plant-orders', [ProjectPlantOrdersController::class, 'index']);
Route::get('project-plant-orders/{id}', [ProjectPlantOrdersController::class, 'show']);
Route::post('consume-items', [ProjectPlantOrdersController::class, 'consumeItems']);
Route::post('return-items', [ProjectPlantOrdersController::class, 'returnItems']);

Route::get('accepting-stats', [AcceptingStatsController::class, 'index']);

Route::get('/', [InventoryController::class, 'index']);
Route::get('/branch-inventory', [InventoryController::class, 'branchInventory']);
Route::get('/status', [InventoryController::class, 'status']);
Route::post('/populate', [InventoryController::class, 'populateInventory']);
Route::get('/histories/{id}', [InventoryController::class, 'histories']);
Route::patch('triggers/{id}', [TriggersController::class, 'update']);
Route::patch('price/{id}', [TriggersController::class, 'updatePrice']);

Route::post('/repack', [InventoryController::class, 'repackItem']);
Route::patch('/beginning-balance/{id}', [InventoryController::class, 'updateBeginningBalance']);

Route::get('/item-costing', [InventoryController::class, 'itemCosting']);
Route::get('/warehouse-issuances', [InventoryController::class, 'warehouseIssuances']);
Route::get('/inputs-of-receipts', [InventoryController::class, 'inputsOfReceipts']);

Route::get('/dashboard-data', [InventoryController::class, 'dashboardData']);

Route::get('/notifications', [RequisitionController::class, 'getNotifications']);
Route::delete('requisition/{id}', [RequisitionController::class,'deleteRequest']);
Route::post('requisition-decline/{id}', [ApprovingRequisitionController::class,'decline']);