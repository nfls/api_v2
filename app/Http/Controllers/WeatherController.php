<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Response;
use Cookie;

class WeatherController extends Controller
{
    function ping(Request $request){
        return Response::json(array("code"=>200, "message"=>"pong"));
    }

    function testKey(Request $request){
        if($request->only("key") && $request->has("key") && $request->isMethod("POST")){
            $station = DB::connection("mysql_user")->table("weather_station")->where(["update_key"=>$request->input("key")])->first();
            if(!is_null($station->id)){
                return Response::json(array("code"=>200, "message"=>$station->id));
            } else {
                return Response::json(array("code"=>404, "message"=>"No such key."));
            }
        }
        return Response::json(array("code"=>403, "message"=>"Forbidden."));
    }

    function updateStation(Request $request){

    }

    function updateData(Request $request){

    }

    function getStationList(Request $request){

    }

    function getStationInfo(Request $request){

    }

    function getRealtimeData(Request $request){

    }

    function getHistoryData(Request $request){

    }
}
