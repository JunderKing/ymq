<?php

use Illuminate\Http\Request;

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
Route::any('/', function() {
    return date('Y-m-d H:i:s');
});

Route::group(['prefix' => 'user'], function() {
    // 不需要登录
    Route::any('wxLogin', 'UserController@wxLogin');
});

Route::group(['prefix' => 'activity'], function () {
    Route::any('list', 'ActivityController@list');
    Route::any('detail', 'ActivityController@detail');
    Route::group(['middleware' => 'checkLogin'], function() {
        Route::any('join', 'ActivityController@join');
        Route::any('create', 'ActivityController@create');
    });
});

