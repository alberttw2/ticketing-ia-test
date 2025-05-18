<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstablishmentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ItemProductMappingController;

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Establishments
Route::resource('establishments', EstablishmentController::class);
Route::get('establishments/{id}/template', [EstablishmentController::class, 'viewTemplate'])->name('establishments.template.view');
Route::post('establishments/{id}/template', [EstablishmentController::class, 'updateTemplate'])->name('establishments.template.update');

// Products
Route::resource('products', ProductController::class);
Route::get('products/{id}/export', [ProductController::class, 'export'])->name('products.export');

// Tickets
Route::resource('tickets', TicketController::class);
Route::get('tickets/{id}/image', [TicketController::class, 'showImage'])->name('tickets.image');
Route::post('tickets/{id}/items', [TicketController::class, 'updateItems'])->name('tickets.items.update');
Route::delete('ticket-items/{id}', [TicketController::class, 'deleteItem'])->name('ticket.items.delete');

// Item-Product Mappings
Route::resource('mappings', ItemProductMappingController::class);

// Auth routes (if needed)
// Route::middleware(['auth'])->group(function () {
//     // Protected routes
// });
