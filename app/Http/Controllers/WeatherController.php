<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Response;

class WeatherController extends Controller
{
    function ping(Request $request){
        return Response::json(array("message"=>"ping"));
    }

    function getStationList(Request $request){

    }

    function getRealtimeData(Request $request){

    }

    function getHistoryData(Request $request){

    }
}
