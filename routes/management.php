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

    // Accessible to other authenticated users
    Route::apiResource('branches', BranchesController::class)->only(['index', 'show']);
    Route::apiResource('suppliers', SupplierController::class)->only(['index', 'show'])->parameters(['suppliers' => 'id']);
    Route::apiResource('products', ProductsController::class)->only(['index', 'show']);
    Route::apiResource('categories', CategoriesController::class)->only(['index', 'show']);
    Route::apiResource('users', UsersController::class)->only(['index', 'show']);
    Route::apiResource('units', UnitsController::class)->only(['index', 'show']);


    Route::middleware("role:{$adminRoles}")->group(function () {
        Route::apiResource('users', UsersController::class)->except(['index', 'show']);
        Route::apiResource('categories', CategoriesController::class)->except(['index', 'show']);
        Route::apiResource('units', UnitsController::class)->except(['index', 'show']);
        Route::apiResource('branches', BranchesController::class)->except(['index', 'show']);
        Route::apiResource('suppliers', SupplierController::class)->except(['index', 'show'])->parameters(['suppliers' => 'id']);
        Route::apiResource('products', ProductsController::class)->except(['index', 'show']);
        Route::post('import-products', [ProductsController::class, 'import']);
        Route::post('import-suppliers', [SupplierController::class, 'import']);
        Route::get('get-model-history', [UsersController::class, 'getModelHistory']);
        Route::get('get-product-with-stock/{id}', [ProductsController::class, 'getProductWithStock']);

        Route::patch('user-password/{id}', [UserPasswordController::class, 'update']);
    });
});
