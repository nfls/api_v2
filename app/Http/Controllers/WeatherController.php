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
            $data_asoc = json_decode($request->input("data"),true);
            $id = $this->getStation($request->input("key"));
            $configurations = $this->getConfiguration($id);
            if(count($data_asoc) != count($configurations))
                return Response::json(array("code"=>403,"info"=>count($data_asoc)),403);
            $final_data = array();
            foreach ($configurations as $index => $configuration){
                if($configuration["isEnabled"] == true) {
                    $flag = false;
                    foreach ($data_asoc as $key => $value) {
                        if ($configuration["identification"] == $key) {
                            array_push($final_data, $value);
                            $flag = true;
                        }
                    }
                    if (!$flag) {
                        abort(404.1);
                    }
                }
            }
            if(count($final_data) != count($configurations)){
                return Response::json(array("code"=>403,"info"=>count($final_data)),403);
            }
            $conf_id = DB::connection("mysql_user")->table("weather_station")->where(["id"=>$id])->first()->current_configuration;
            DB::connection("mysql_user")->table("weather_history")->insert(["update_time"=>date('Y-m-d H:i:s'),
                "update_ip"=>$_SERVER['REMOTE_ADDR'],
                "station_id"=>$id,
                "configuration_id"=>$conf_id,
                "data"=>json_encode($final_data)]);
            DB::connection("mysql_user")->table("weather_station")->where(["id"=>$id])->update(["lastupdate"=>date('Y-m-d H:i:s'),
                "lastupdate_ip"=>$_SERVER['REMOTE_ADDR'],
                "data"=>json_encode($final_data)]);
            return Response::json(array("code"=>200));
        } else {
            return(403);
        }
    }

    function getStationList(Request $request){
        $stations = DB::connection("mysql_user")->table("weather_station")->get();
        $return_array = array();
        foreach($stations as $station){
            if($station->isEnabled) {
                $info["id"] = $station->id;
                $info["name"] = $station->name;
                $info["lastupdate"] = $station->lastupdate;
                if (strtotime(date('Y-m-d h:i:s', strtotime('-10 minutes'))) < strtotime($station->lastupdate)) {
                    $info["isOnline"] = true;
                } else {
                    $info["isOnline"] = false;
                }
                $info["latitude"] = $station->latitude;
                $info["longitude"] = $station->longitude;
                $info["altitude"] = $station->altitude;
                array_push($return_array, $info);
            }
        }
        return Response::json(array("code"=>200,"info"=>$return_array));
    }

    function getStationData(Request $request){
        if($request->only(["id"]) && $request->has(["id"]) && $request->isMethod("POST")) {
            $station = DB::connection("mysql_user")->table("weather_station")->where(["id"=>$request->input(["id"])])->first();
            if(is_null($station)){
                abort(404.1);
            } else {
                $confs = $this->getConfiguration($request->input("id"));
                $data = json_decode($station->data,true);
                $return_array = array();
                $i=0;
                foreach($confs as $conf){
                    if($conf["isVisible"]){
                        $info['name'] = $conf["visualName"];
                        $info['id'] = $conf["identification"];
                        $info['sensor_name'] = $conf["visualSensorName"];
                        $info['isDigital'] = $conf["isDigital"];
                        $info['unit'] = $conf["unit"];
                        $info['value'] = $data[$i];
                        $i++;
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
                $i = 0;
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
        if($request->only(["id","data_index","range"]) && $request->has(["id","data_index"])){
            $station = DB::connection("mysql_user")->table("weather_station")->where(["id"=>$request->input(["id"])])->first();
        }
    }



}
