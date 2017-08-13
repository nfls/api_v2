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

    function getStation($key){
        $station = DB::connection("mysql_user")->table("weather_station")->where(["update_key"=>$key])->first();
        if(is_null($station->id))
            abort(404);
        else
            return $station->id;
    }

    function getConfiguration($id){
        $conf_id = DB::connection("mysql_user")->table("weather_station")->where(["id"=>$id])->first();
        $conf = DB::connection("mysql_user")->table("weather_configuration")->where(["id"=>$conf_id])->first()->configuration;
        return json_decode($conf,true);
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


    function addStation(Request $request){
        /*
         * json-array style:
         * data-name-in-english
         * chinese-name
         * shown default or raw data
         */
        $altitude = $request->input("altitude");
        $latitude = $request->input("latitude");
        $longitude = $request->input("longitude");
        $structure = $request->input("structure");
    }

    function updateData(Request $request){
        if($request->only(["data","key"]) && $request->has(["data","key"]) && $request->isMethod("POST")){
            $data_asoc = $request->input("data");
            $id = $this->getStation($request->input("key"));
            $configurations = $this->$this->getConfiguration($id);
            if(count($data_asoc) != count($configurations))
                abort(1001);
            $final_data = array();
            foreach ($configurations as $index => $configuration){
                if($configuration["isEnabled"] == true){
                    $flag = false;
                    foreach ($data_asoc as $ins){
                        if($ins["identification"] = $ins["dataname"]){
                            array_push($final_data,$ins["data"]);
                            $flag = true;
                        }
                    }
                    if(!$flag){
                        abort(404.1);
                    }
                }
                $conf_id = DB::connection("mysql_user")->table("weather_station")->where(["id"=>$id])->first();
                DB::connection("mysql_user")->table("weather_history")->insert(["update_time"=>date(),
                    "update_ip"=>$_SERVER['REMOTE_ADDR'],
                    "configuration_id"=>$conf_id,
                    "data"=>json_encode($final_data)]);
                DB::connection("mysql_user")->table("weather_station")->where(["id"=>$id])->update(["lastupdate"=>time(),
                    "lastupdate_ip"=>$_SERVER['REMOTE_ADDR'],
                    "data"=>json_encode($final_data)]);
            }
        } else {
            return(403);
        }
    }

    function getStationList(Request $request){

    }

    function getStationInfo(Request $request){

    }

    function getRealtimeData(Request $request){

    }

    function getHistoryData(Request $request){

    }

    function prepareTables($id,$count){

    }
}
