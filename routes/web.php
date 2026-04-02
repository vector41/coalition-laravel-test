<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
Route::get('/inventory/items', [InventoryController::class, 'indexJson'])->name('inventory.items');
Route::post('/inventory/items', [InventoryController::class, 'store'])->name('inventory.store');
Route::put('/inventory/items/{id}', [InventoryController::class, 'update'])->name('inventory.update');
