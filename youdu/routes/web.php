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

// V2 API
Route::match(['get', 'post'], '/v2/book/{action?}', 'BookController');
// 为了匹配 /v2/isbn/xxx 这样的 url，有更好的方法吗？
Route::match(['get', 'post'], '/v2/book/isbn/{isbn}', 'BookIsbnController');
Route::match(['get', 'post'], '/v2/card/{action?}', 'CardController');
Route::match(['get', 'post'], '/v2/{action?}', 'Api2Controller');
