<?php

use App\Http\Controllers\ProductController;
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

Route::middleware('salesforce-token')->group(function () {
    Route::get('/products', [ProductController::class,'fetchProducts']);
    Route::get('/insert-products', [ProductController::class,'insertProductsFromSalesforceToDatabase']);
    Route::get('/fetch-products/{area?}/{productId?}', [ProductController::class,'fetchProductsFromDatabase']);
    Route::get('/fetch-data-from-salesforce', [ProductController::class,'fetchProductsFromSalesforce']);
});
