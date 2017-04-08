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

Route::post('alumni/auth/{step}/update',"AlumniController@AuthUpdate");
Route::get('alumni/auth/{step}/query',"AlumniController@AuthQuery");
Route::get('alumni/auth/getCurrentStep',"AlumniController@getCurrentStep");