<?php

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
});
Route::match(['get', 'post'], '/m/{action?}', 'MobileController');
Route::match(['get', 'post'], '/api/{action?}', 'ApiController');
Route::match(['get', 'post'], '/v2/book/{action?}', 'BookController');
Route::match(['get', 'post'], '/v2/card/{action?}', 'CardController');