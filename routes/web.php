<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name("home");

Route::name('admin.')->middleware(['auth.basic'])->prefix('admin')->group(function () {
    Route::get('/', "AdminController@inicio")->name("inicio");
    Route::get('/locales', "AdminController@localesDomicilio")->name("locales");
    Route::get('/jobs-fallidos', "AdminController@jobsFallidos")->name("jobs-fallidos");

    Route::get('/guardar-conexion', "AdminController@guardarConexion")->name("guardar-conexion");
    Route::get('/probar-conexion', "AdminController@probarConexionBDD")->name("probar-conexion");
    Route::get('/probar-ping', "AdminController@probarPing")->name("probar-ping");
    Route::get('/reintentar-job', "AdminController@reintentarJob")->name("reintentar-job");
    Route::get('/json-job', "AdminController@jsonJob")->name("json-job");
});

Route::get('logout-basic', function() {
    Auth::logout();
    return abort(401);
})->name("logout-basic");