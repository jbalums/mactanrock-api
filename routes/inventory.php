<?php

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


Route::get('receiving',[ReceivingController::class,'index']);
Route::get('receiving/{id}',[ReceivingController::class,'show']);
Route::post('receiving', [ReceivingController::class,'store']);
Route::patch('receiving-complete',[ReceivingCompleteController::class,'update']);

Route::get('requisition',[RequisitionController::class,'index']);
Route::get('request',[RequestController::class,'index']);
Route::get('request/{id}',[RequestController::class,'show']);
Route::post('requisition', [RequisitionController::class,'store']);
Route::post('requisition-approved/{id}', [ApprovingRequisitionController::class,'update']);
Route::get('requisition/{id}',[RequisitionController::class,'show']);


Route::get('project-plant-orders',[ProjectPlantOrdersController::class,'index']);
Route::get('project-plant-orders/{id}',[ProjectPlantOrdersController::class,'show']);

Route::get('accepting-stats',[AcceptingStatsController::class,'index']);

Route::get('/', [InventoryController::class, 'index']);
Route::get('/branch-inventory', [InventoryController::class, 'branchInventory']);
Route::get('/status', [InventoryController::class, 'status']);
Route::get('/histories/{id}', [InventoryController::class, 'histories']);
Route::patch('triggers/{id}', [TriggersController::class, 'update']);
