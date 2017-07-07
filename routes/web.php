<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware ·group. Now create something great!
|
*/
//header("Access-Control-Allow-Origin: http://localhost:63343");
Route::post('alumni/auth/{step}/update',"CertificationController@authUpdate");
Route::get('alumni/auth/{step}/query',"CertificationController@authQuery");
Route::get('alumni/auth/step',"CertificationController@getCurrentStep");
Route::get('alumni/auth/status',"CertificationController@getCurrentStatus");
Route::get('alumni/auth/instructions',"CertificationController@getInstructions");
Route::get('alumni/auth/duration',"CertificationController@getDuration");

Route::any('center/{type}',"UserCenterController@requestHandler");

Route::get('media/gallery/list/{id}',"AlbumController@getPhotoList");
Route::get('media/gallery/info/{id}',"AlbumController@getAlbumInfo");
Route::get('media/gallery/getlist',"AlbumController@getAlbumList");
Route::any('media/gallery/update',"AlbumController@updateAnAlbum");
Route::get("admin/auth/list","CertificationManagementController@getSubmittedUserList");

Route::post('device/register',"IOSDeviceController@registerDevice");
Route::post('device/purchase',"IOSDeviceController@iapPurchase");