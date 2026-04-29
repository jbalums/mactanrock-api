<?php

use App\Http\Resources\UserLogsResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('', function () {
    return 'API WORKS';
});

Route::post('/login', [\App\Http\Controllers\LoginController::class, 'store']);

Route::middleware(['auth:sanctum'])->get('/user-test', function (Request $request) {
    return  $user = $request->user();
});
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    $user = $request->user();
    $user->load('branch');
    return UserResource::make($user);
});
Route::middleware(['auth:sanctum'])->get('/user-logs', function (Request $request) {
    $user = $request->user();
    $user->load('operations');
    return UserLogsResource::collection($user->operations);
});

Route::middleware(['guest'])->group(function () {
    Route::prefix('public')->group(function () {
        require __DIR__ . '/public.php';
    });
});


Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [\App\Http\Controllers\LogoutController::class, 'store']);

    require  __DIR__ . '/management.php';

    Route::prefix('inventory')->group(function () {
        require __DIR__ . '/inventory.php';
    });

    Route::prefix('v2')->group(function () {
        require __DIR__ . '/v2/inventory.php';
    });
});
