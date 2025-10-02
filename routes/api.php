<?php

use App\Http\Controllers\AccompanimentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\NotificationController;


Broadcast::routes(['middleware' => ['auth:sanctum']]);
// ---------------------------
// Authentification (exemple Sanctum)
// ---------------------------
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard_server', [UserController::class, 'dashboardServer']);
// ---------------------------
// Catégories
// ---------------------------
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('accompaniments', AccompanimentController::class);

// ---------------------------
// Produits
// ---------------------------
    Route::apiResource('products', ProductController::class);
    Route::post('product_update', [ProductController::class, 'updatePart']);
// ---------------------------
// Tables
// ---------------------------
    Route::apiResource('tables', TableController::class);
    Route::post('tables/{id}/status', [TableController::class, 'updateStatus']);

// ---------------------------
// Commandes
// ---------------------------
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('users', UserController::class);

// ---------------------------
// Items de commande
// ---------------------------
    Route::post('orders/{order}/items', [OrderItemController::class, 'store']);
    Route::post('items/{item}/supplements', [OrderItemController::class, 'addSupplements']);
    Route::post('items/{item}/accompaniments', [OrderItemController::class, 'addAccompaniments']);
    Route::patch('items/{item}/quantity', [OrderItemController::class, 'updateQuantity']);
    Route::patch('items/{item}/status', [OrderItemController::class, 'updateStatus']);
    Route::delete('items/{item}', [OrderItemController::class, 'destroy']);

// ---------------------------
// Paiements
// ---------------------------
    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('payments/{order}/orders', [PaymentController::class, 'paymentByOrder']);
    Route::post('payments/{order}/orders', [PaymentController::class, 'store']);
    Route::get('payments/stats', [PaymentController::class, 'paymentStat']);

// ---------------------------
// Cuisine / KDS
// ---------------------------
    Route::get('kitchen/orders', [KitchenController::class, 'pendingOrders']);
    Route::post('kitchen/items/{item}/ready', [KitchenController::class, 'markItemReady']);

// ---------------------------
// Notifications temps réel
// ---------------------------
    Route::post('notifications/send', [NotificationController::class, 'send']);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);
});

