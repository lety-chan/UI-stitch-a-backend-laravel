<?php

use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\RestockController;
use App\Http\Controllers\SaludoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------|
| Web Routes
|--------------------------------------------------------------------------|
*/

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Rutas del controlador Saludo (MVC)
Route::get('/saludo', [SaludoController::class, 'create'])->name('saludo.create');
Route::post('/saludo', [SaludoController::class, 'store'])->name('saludo.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/profile', [UserController::class, 'showProfile'])->name('profile.show');
    Route::match(['put', 'patch'], '/profile', [UserController::class, 'updateProfile'])->name('profile.update');

    Route::get('/inventario', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventario/crear', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('/inventario', [InventoryController::class, 'store'])->name('inventory.store');
    Route::post('/inventario/bodegas', [InventoryController::class, 'storeWarehouse'])->name('inventory.warehouses.store');
    Route::post('/inventario/proveedores', [InventoryController::class, 'storeSupplier'])->name('inventory.suppliers.store');

    Route::get('/analitica', [AnalyticsController::class, 'index'])->name('analytics.index');

    Route::get('/reabastecer', [RestockController::class, 'index'])->name('restock.index');
    Route::post('/reabastecer/autoaprobar', [RestockController::class, 'toggleAutoApprove'])->name('restock.toggle-auto-approve');
    Route::post('/reabastecer/productos/{product}/proveedor', [RestockController::class, 'assignSupplier'])->name('restock.products.supplier');
    Route::post('/reabastecer/productos/{product}/ordenes', [RestockController::class, 'generateOrder'])->name('restock.orders.store');
    Route::post('/reabastecer/ordenes/aprobar-todas', [RestockController::class, 'approveAll'])->name('restock.approve-all');
    Route::post('/reabastecer/ordenes/{purchaseOrder}/aprobar', [RestockController::class, 'approveOrder'])->name('restock.orders.approve');
    Route::post('/reabastecer/ordenes/{purchaseOrder}/recibir', [RestockController::class, 'receiveOrder'])->name('restock.orders.receive');

    Route::get('/asistente-ia', [AiAssistantController::class, 'index'])->name('assistant.index');
    Route::post('/asistente-ia', [AiAssistantController::class, 'send'])->name('assistant.send');
    Route::post('/asistente-ia/movimientos/{inventoryMovement}/confirmar', [AiAssistantController::class, 'confirmMovement'])->name('assistant.movements.confirm');
    Route::post('/asistente-ia/movimientos/{inventoryMovement}/cancelar', [AiAssistantController::class, 'cancelMovement'])->name('assistant.movements.cancel');
});
