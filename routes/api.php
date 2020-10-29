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

Route::group(['prefix' => 'course'], function () {
    Route::any('list', 'CourseController@list');
    Route::any('info', 'CourseController@info');
    Route::any('detail', 'CourseController@detail');
    Route::group(['middleware' => 'checkLogin'], function() {
        Route::any('createOrUpdate', 'CourseController@createOrUpdate');
    });
});

Route::group(['prefix' => 'lesson'], function () {
    Route::any('detail', 'LessonController@detail');
    Route::group(['middleware' => 'checkLogin'], function() {
        Route::any('cancel', 'LessonController@cancel');
        Route::any('join', 'LessonController@join');
        Route::any('delete', 'LessonController@delete');
        Route::any('createOrUpdate', 'LessonController@createOrUpdate');
    });
});

Route::group(['prefix' => 'address'], function() {
    Route::any('createOrUpdate', 'AddressController@createOrUpdate');
    Route::any('list', 'AddressController@list');
});

