<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cookie;
use Response;
use Databse;
class IOSDeviceController extends Controller
{
    function registerDevice(Request $request){
        $device_id = $request->input("device_id");
        $device_model = $request->input("device_model");
        if(strlen($device_id)!=32)
            abort(403);
        DB::connection("mysql_user")->table("user_device")->insert(["device_id"=>$device_id,"device_model"=>$device_model]);
        return Response::json(array("code"=>200, "status"=>"succeed"));
    }
}
