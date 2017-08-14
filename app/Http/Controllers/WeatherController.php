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
        $conf_id = DB::connection("mysql_user")->table("weather_station")->where(["id"=>$id])->first()->current_configuration;
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
            $configurations = $this->getConfiguration($id);
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
                $conf_id = DB::connection("mysql_user")->table("weather_station")->where(["id"=>$id])->first()->current_configuration;
                DB::connection("mysql_user")->table("weather_history")->insert(["update_time"=>date('Y-m-d h:i:s'),
                    "update_ip"=>$_SERVER['REMOTE_ADDR'],
                    "station_id"=>$id,
                    "configuration_id"=>$conf_id,
                    "data"=>json_encode($final_data)]);
                DB::connection("mysql_user")->table("weather_station")->where(["id"=>$id])->update(["lastupdate"=>date('Y-m-d h:i:s'),
                    "lastupdate_ip"=>$_SERVER['REMOTE_ADDR'],
                    "data"=>json_encode($final_data)]);
            }
            return Response::json(array("code"=>200));
        } else {
            return(403);
        }
    }

    function getStationList(Request $request){
        $stations = DB::connection("mysql_user")->table("weather_station")->get();
        $return_array = array();
        foreach($stations as $station){
            $info["id"] = $station->id;
            $info["name"] = $station->name;
            $info["isEnabled"] = $station->isEnabled;
            array_push($return_array,$info);
        }
        return Response::json(array("code"=>200,"info"=>$return_array));
    }

    function getStationInfo(Request $request){
        if($request->only(["id"]) && $request->has(["id"]) && $request->isMethod("POST")) {
            $station = DB::connection("mysql_user")->table("weather_station")->where(["id"=>$request->input(["id"])])->first();
            if(is_null($station)){
                abort(404.1);
            } else {
                $confs = $this->getConfiguration($request->input("id"));
                $return_array = array();
                foreach($confs as $conf){
                    if($conf["isVisible"]){
                        $info['name'] = $conf["visualName"];
                        $info['id'] = $conf["identification"];
                        $info['sensor_name'] = $conf["visualSensorName"];
                        $info['isDigital'] = $conf["idDigital"];
                        array_push($return_array,$info);
                    }
                }
                return Response::json(array("code"=>200,"info"=>$return_array));
            }
        }

    }

    function getRealtimeData(Request $request){
        if($request->only(["id"]) && $request->has(["id"]) && $request->isMethod("POST")) {
            $station = DB::connection("mysql_user")->table("weather_station")->where(["id"=>$request->input(["id"])])->first();
            if(is_null($station)){
                abort(404.1);
            } else {
                $data = json_decode($station->data,true);
                $confs = $this->getConfiguration($request->input("id"));
                $return_array = array();
                $i = 0;ßßß
                foreach($confs as $conf){
                    $i++;
                    if($conf["isVisible"]){
                        array_push($return_array,$data[$i]);
                    }
                }
                return Response::json(array("code"=>200,"info"=>$return_array));
            }
        }

    }

    function getHistoryData(Request $request){

    }

    function prepareTables($id,$count){
        $i=9;
    }
}
