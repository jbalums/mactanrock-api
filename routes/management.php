<?php

use App\Enums\UserType;
use App\Http\Controllers\Managements\BranchesController;
use App\Http\Controllers\Managements\CategoriesController;
use App\Http\Controllers\Managements\PasswordController;
use App\Http\Controllers\Managements\ProductsController;
use App\Http\Controllers\Managements\SupplierController;
use App\Http\Controllers\Managements\UserPasswordController;
use App\Http\Controllers\Managements\UsersController;
use App\Http\Controllers\UnitsController;
use Illuminate\Support\Facades\Route;

$adminRoles = UserType::ADMIN->value;

Route::prefix('management')->group(function () use ($adminRoles) {
    Route::patch('password', [PasswordController::class, 'update']);

    Route::middleware("role:{$adminRoles}")->group(function () {
        Route::apiResource('users', UsersController::class);
        Route::apiResource('categories', CategoriesController::class);
        Route::apiResource('units', UnitsController::class);
        Route::apiResource('branches', BranchesController::class);
        Route::apiResource('suppliers', SupplierController::class)->parameters(['suppliers' => 'id']);
        Route::apiResource('products', ProductsController::class);
        Route::post('import-products', [ProductsController::class, 'import']);
        Route::post('import-suppliers', [SupplierController::class, 'import']);
        Route::get('get-model-history', [UsersController::class, 'getModelHistory']);
        Route::get('get-product-with-stock/{id}', [ProductsController::class, 'getProductWithStock']);

        Route::patch('user-password/{id}', [UserPasswordController::class, 'update']);
    });
});
