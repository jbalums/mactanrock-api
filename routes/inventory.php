<?php

use App\Enums\UserType;
use App\Http\Controllers\ApprovingManager\RequisitionsController;
use App\Http\Controllers\CorrectionController;
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

$adminRoles = implode(',', [
    UserType::ADMIN->value,
]);

$approvalRoles = implode(',', [
    UserType::ADMIN->value,
    UserType::AREA_MANAGER->value,
    UserType::APPROVING_MANAGER->value,
    UserType::WAREHOUSE_MAN->value,
]);

$operationalRoles = implode(',', [
    UserType::ADMIN->value,
    UserType::WAREHOUSE_MAN->value,
    UserType::AREA_MANAGER->value,
    UserType::APPROVING_MANAGER->value,
    UserType::BU_MANAGER->value,
]);

$requesterRoles = implode(',', [
    UserType::ADMIN->value,
    UserType::WAREHOUSE_MAN->value,
    UserType::AREA_MANAGER->value,
    UserType::APPROVING_MANAGER->value,
    UserType::BU_MANAGER->value,
    UserType::EMPLOYEE->value,
]);

Route::get('receiving', [ReceivingController::class, 'index']);
Route::get('receiving/{id}', [ReceivingController::class, 'show']);
Route::post('receiving', [ReceivingController::class, 'store'])->middleware("role:{$operationalRoles}");
Route::patch('receiving-complete/{id}', [ReceivingCompleteController::class, 'update'])->middleware("role:{$operationalRoles}");

Route::get('requisition', [RequisitionController::class, 'index']);
Route::get('request', [RequestController::class, 'index']);
Route::get('request/{id}', [RequestController::class, 'show']);
Route::post('requisition', [RequisitionController::class, 'store'])->middleware("role:{$requesterRoles}");
Route::patch('requisition/{id}', [RequisitionController::class, 'update'])->middleware("role:{$requesterRoles}");
Route::post('requisition-approved/{id}', [ApprovingRequisitionController::class, 'update'])->middleware("role:{$approvalRoles}");
Route::get('requisition/{id}', [RequisitionController::class, 'show']);
Route::post('requisition-accept/{id}', [RequisitionController::class, 'accept'])->middleware("role:{$approvalRoles}");

Route::post('approve-issuance/{id}', [RequisitionController::class, 'approveIssuance'])->middleware("role:{$approvalRoles}");

Route::get('issuances', [RequisitionController::class, 'issuances']);
Route::get('approval-issuances', [RequisitionController::class, 'issuancesForApproval'])->middleware("role:{$approvalRoles}");

Route::get('get-receiving-issuances', [RequisitionController::class, 'receivingIssuances']);

Route::post('issuances/{id}', [RequisitionController::class, 'createIssuances'])->middleware("role:{$operationalRoles}");
Route::post('issuances-recieved/{id}', [RequisitionController::class, 'receivedIssuances'])->middleware("role:{$operationalRoles}");

Route::get('project-plant-orders', [ProjectPlantOrdersController::class, 'index']);
Route::get('project-plant-orders/{id}', [ProjectPlantOrdersController::class, 'show']);
Route::post('consume-items', [ProjectPlantOrdersController::class, 'consumeItems'])->middleware("role:{$operationalRoles}");
Route::post('return-items', [ProjectPlantOrdersController::class, 'returnItems'])->middleware("role:{$operationalRoles}");

Route::get('accepting-stats', [AcceptingStatsController::class, 'index'])->middleware("role:{$approvalRoles}");

Route::get('/', [InventoryController::class, 'index']);
Route::get('/branch-inventory', [InventoryController::class, 'branchInventory']);
Route::get('/status', [InventoryController::class, 'status']);
Route::post('/populate', [InventoryController::class, 'populateInventory'])->middleware("role:{$adminRoles}");
Route::get('/histories/{id}', [InventoryController::class, 'histories']);
Route::get('/transaction-histories', [InventoryController::class, 'inventoryTransactionHistories']);
Route::patch('/inventory-correction', [InventoryController::class, 'inventoryCorrection'])->middleware("role:{$adminRoles}");

Route::patch('triggers/{id}', [TriggersController::class, 'update'])->middleware("role:{$adminRoles}");
Route::patch('price/{id}', [TriggersController::class, 'updatePrice'])->middleware("role:{$adminRoles}");



Route::post('/repack', [InventoryController::class, 'repackItem'])->middleware("role:{$adminRoles}");
Route::patch('/beginning-balance/{id}', [InventoryController::class, 'updateBeginningBalance'])->middleware("role:{$adminRoles}");

Route::get('/item-costing', [InventoryController::class, 'itemCosting']);
Route::get('/warehouse-issuances', [InventoryController::class, 'warehouseIssuances']);
Route::get('/inputs-of-receipts', [InventoryController::class, 'inputsOfReceipts']);

Route::get('/dashboard-data', [InventoryController::class, 'dashboardData']);

Route::get('/notifications', [RequisitionController::class, 'getNotifications']);
Route::delete('requisition/{id}', [RequisitionController::class, 'deleteRequest'])->middleware("role:{$adminRoles}");
Route::post('requisition-decline/{id}', [ApprovingRequisitionController::class, 'decline'])->middleware("role:{$approvalRoles}");
Route::post('requisition-delete/{id}', [ApprovingRequisitionController::class, 'delete'])->middleware("role:{$approvalRoles}");


Route::post('AUzNo13OhD1ONaRO/correction', [CorrectionController::class, 'correction'])->middleware("role:{$adminRoles}");
