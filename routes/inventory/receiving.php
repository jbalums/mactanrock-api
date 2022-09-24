<?php

use App\Http\Controllers\Inventory\ReceivingCompleteController;
use App\Http\Controllers\Inventory\ReceivingController;
use Illuminate\Support\Facades\Route;


Route::get('receiving',[ReceivingController::class,'index']);
Route::post('receiving', [ReceivingController::class,'store']);
Route::patch('receiving-complete',[ReceivingCompleteController::class,'update']);
