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

Route::group(['prefix' => 'admin'], function() {
    Route::group(['middleware' => 'checkLogin'], function() {
        Route::any('user/list', 'AdminController@userList');
        Route::any('user/lessonAssign', 'AdminController@userLessonAssign');
        Route::any('course/list', 'AdminController@courseList');
        Route::any('course/detail', 'AdminController@courseDetail');
        Route::any('course/update', 'AdminController@courseUpdate');
        Route::any('lesson/update', 'AdminController@lessonUpdate');
    });
});

Route::group(['prefix' => 'user'], function() {
    // 不需要登录
    Route::any('wxLogin', 'UserController@wxLogin');
    Route::any('detail', 'UserController@detail');
    Route::group(['middleware' => 'checkLogin'], function() {
        Route::any('lessonRecord', 'UserController@lessonRecord');
    });
});

Route::group(['prefix' => 'course'], function () {
    Route::any('list', 'CourseController@list');
    Route::any('detail', 'CourseController@detail');
});

Route::group(['prefix' => 'lesson'], function () {
    Route::any('detail', 'LessonController@detail');
    Route::any('myList', 'LessonController@myList');
    Route::group(['middleware' => 'checkLogin'], function() {
        Route::any('join', 'LessonController@join');
        Route::any('cancel', 'LessonController@cancel');
    });
});

Route::group(['prefix' => 'address'], function() {
    Route::any('update', 'AddressController@update');
    Route::any('list', 'AddressController@list');
});

