<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cookie;
use Response;
use Illuminate\Support\Facades\DB;

class IOSDeviceController extends Controller
{
    function registerDevice(Request $request){
        $device_id = $request->input("device_id");
        $device_model = $request->input("device_model");
        if(strlen($device_id)!=64)
            abort(403);
        $device = DB::connection("mysql_user")->table("user_device")->where(["device_id"=>$device_id])->first();
        if(is_null(@$device->device_model)){
            DB::connection("mysql_user")->table("user_device")->insert(["device_id" => $device_id, "device_model" => $device_model, "user_id"=>-1]);
        }
        return Response::json(array("code"=>200, "status"=>"succeed"));
    }
}
