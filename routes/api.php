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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
Route::get('/task/index', ['uses'=>'TasksController@index','as'=>'task.index'])->middleware('auth:api');
Route::post('/task/add', ['uses'=>'TasksController@addTask','as'=>'task.add'])->middleware('auth:api');
Route::post('/task/remove', ['uses'=>'TasksController@removeTask','as'=>'task.remove'])->middleware('auth:api');
Route::post('/task/edit', ['uses'=>'TasksController@editTask','as'=>'task.edit'])->middleware('auth:api');
Route::get('/task/get', ['uses'=>'TasksController@getTask','as'=>'task.get'])->middleware('auth:api');
