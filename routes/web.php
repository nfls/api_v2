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
//header("Access-Control-Allow-Origin: http://localhost:63343");
Route::post('alumni/auth/{step}/update',"AlumniController@AuthUpdate");
Route::get('alumni/auth/{step}/query',"AlumniController@AuthQuery");
Route::get('alumni/auth/getCurrentStep',"AlumniController@getCurrentStep");
Route::get('alumni/auth/back',"AlumniController@backStep");
Route::get('media/gallery/list/{id}',"AlbumController@getPhotoList");
Route::get('media/gallery/info/{id}',"AlbumController@getAlbumInfo");
Route::get('media/gallery/getlist',"AlbumController@getAlbumList");
Route::any('media/gallery/update',"AlbumController@updateAnAlbum");