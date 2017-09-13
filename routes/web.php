<?php
use Illuminate\Http\Request;
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
date_default_timezone_set ( "Asia/Shanghai" );
Route::post('alumni/auth/{step}/update',"CertificationController@authUpdate");
Route::get('alumni/auth/{step}/query',"CertificationController@authQuery");
Route::get('alumni/auth/step',"CertificationController@getCurrentStep");
Route::get('alumni/auth/status',"CertificationController@getCurrentStatus");
Route::get('alumni/auth/instructions',"CertificationController@getInstructions");
Route::get('alumni/auth/duration',"CertificationController@getDuration");
Route::get('alumni/post/list',"AlumniWebsiteController@getPostList");
Route::post('alumni/post/detail',"AlumniWebsiteController@getDetailPost");

Route::any('center/{type}',"UserCenterController@requestHandler");

Route::get('media/gallery/list/{id}',"AlbumController@getPhotoList");
Route::get('media/gallery/info/{id}',"AlbumController@getAlbumInfo");
Route::get('media/gallery/getlist',"AlbumController@getAlbumList");
Route::any('media/gallery/update',"AlbumController@updateAnAlbum");

Route::get("live/list","LiveListController@getLiveList");
Route::get("video/list","LiveListController@getVideoList");

Route::post("student/query","StudentsListController@getNameList");
Route::get("student/info","StudentsListController@getInfo");
Route::post("student/use","StudentsListController@useName");
Route::post("student/unuse","StudentsListController@unuseName");
Route::get("student/list","StudentsListController@getUsedName");

Route::post("university/list","ClubsAndUniversitiesController@listUniversities");
Route::post("university/get","ClubsAndUniversitiesController@getAUniversity");
Route::post("university/edit","ClubsAndUniversitiesController@editUniversity");
Route::post("university/new","ClubsAndUniversitiesController@addUniversity");
Route::get("university/intro","ClubsAndUniversitiesController@getIntro");

Route::post("club/list","ClubsAndUniversitiesController@listClubs");
Route::post("club/get","ClubsAndUniversitiesController@getAClub");
Route::post("club/edit","ClubsAndUniversitiesController@editAClub");
Route::post("club/new","ClubsAndUniversitiesController@addClub");
Route::get("club/intro","ClubsAndUniversitiesController@getClubIntro");

Route::get("admin/auth/list","CertificationManagementController@getSubmittedUserList");
Route::post("admin/auth/detail","CertificationManagementController@getUserDetail");
Route::post("admin/auth/index","CertificationManagementController@generateIndex");
Route::post("admin/auth/accept","CertificationManagementController@acceptIdentity");
Route::post("admin/auth/deny","CertificationManagementController@denyIdentity");
Route::post("admin/auth/ignore","CertificationManagementController@ignoreIdentity");
Route::get("admin/auth/instructions","CertificationManagementController@getInstruction");
Route::get("admin/message/get","ManagementController@getAllMessages");

Route::post('device/register',"IOSDeviceController@registerDevice");
Route::post('device/purchase',"IOSDeviceController@iapPurchase");
Route::get('device/status',"IOSDeviceController@confirmLoggedIn");
Route::get('device/notice',"IOSDeviceController@getNotice");
Route::post('device/auth',"IOSDeviceController@compareAuthDatabase");
Route::get('device/pics',"IOSDeviceController@getStartUpPictures");
Route::get('device/push',"IOSDeviceController@pushAMessage");

Route::get('weather/ping',"WeatherController@ping");
Route::post('weather/test',"WeatherController@testKey");
Route::post('weather/add',"WeatherController@addStation");
Route::post('weather/update',"WeatherController@updateData");
Route::get('weather/list',"WeatherController@getStationList");
Route::post('weather/data',"WeatherController@getStationData");
Route::post('weather/history',"WeatherController@getHistoryData");

Route::get("redirect", function(Request $request){
    return redirect($request->input("to"));
});