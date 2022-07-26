<?php

use App\Http\Controllers\Managements\PasswordController;
use App\Http\Controllers\Managements\UsersController;
use Illuminate\Support\Facades\Route;


Route::prefix('management')->group( function (){
    Route::apiResource('users', UsersController::class)
        ->parameter('users','id');
    Route::patch('password',[PasswordController::class,'update']);
});
