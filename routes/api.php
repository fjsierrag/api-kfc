<?php

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

Route::prefix('restApp')->group(function () {
    Route::post('/pedidoApp', "Api\DomicilioController@pedidoApp");
    Route::post('/estadoPedidoApp', "Api\DomicilioController@estadoPedidoApp");
});

Route::prefix('restConfig')->group(function () {
    Route::post('/pingGeneral', "Api\DomicilioController@pingGeneral");
    Route::post('/pingPorTienda', "Api\DomicilioController@pingPorTienda");
});
