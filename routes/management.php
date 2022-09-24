<?php

use App\Http\Controllers\Managements\BranchesController;
use App\Http\Controllers\Managements\CategoriesController;
use App\Http\Controllers\Managements\PasswordController;
use App\Http\Controllers\Managements\ProductsController;
use App\Http\Controllers\Managements\UsersController;
use Illuminate\Support\Facades\Route;


Route::prefix('management')->group( function (){
    Route::apiResource('users', UsersController::class);
    Route::apiResource('categories', CategoriesController::class);
    Route::apiResource('branches', BranchesController::class);
    Route::apiResource('products', ProductsController::class);
    Route::patch('password',[PasswordController::class,'update']);
});
