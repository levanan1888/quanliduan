<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocsController;

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

Route::get('/', [DocsController::class, 'apiHtml']);

// Tài liệu API đơn giản
Route::get('/api-docs', [DocsController::class, 'apiHtml']);
Route::get('/api-docs.json', [DocsController::class, 'apiJson']);

// Export Postman Collection
Route::get('/postman-collection', [\App\Http\Controllers\PostmanCollectionController::class, 'export']);
Route::get('/postman-collection/download', [\App\Http\Controllers\PostmanCollectionController::class, 'download']);
