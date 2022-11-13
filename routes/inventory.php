<?php

use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\Inventory\ReceivingCompleteController;
use App\Http\Controllers\Inventory\ReceivingController;
use App\Http\Controllers\Inventory\RequestController;
use App\Http\Controllers\Inventory\RequisitionController;
use Illuminate\Support\Facades\Route;


Route::get('receiving',[ReceivingController::class,'index']);
Route::get('receiving/{id}',[ReceivingController::class,'show']);
Route::post('receiving', [ReceivingController::class,'store']);
Route::patch('receiving-complete',[ReceivingCompleteController::class,'update']);

Route::get('requisition',[RequisitionController::class,'index']);
Route::get('request',[RequestController::class,'index']);
Route::get('request/{id}',[RequestController::class,'show']);
Route::post('requisition', [RequisitionController::class,'store']);
Route::get('requisition/{id}',[RequisitionController::class,'show']);

Route::get('/', [InventoryController::class,'index']);
