<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cookie;
use Illuminate\Pagination\PaginationServiceProvider;
use Response;
use Illuminate\Support\Facades\DB;

class IOSDeviceController extends Controller
{
    function registerDevice(Request $request){
        if($request->has(["device_id","device_model"]) && $request->only(["device_id","device_model"])){
            $device_id = $request->input("device_id");
            $device_model = $request->input("device_model");
            $user_id = UserCenterController::GetUserId(Cookie::get("token"));
            if(strlen($device_id)!=64)
                abort(403);
            $device = DB::connection("mysql_user")->table("user_device")->where(["device_id"=>$device_id])->first();
            if(is_null(@$device->device_model)){
                DB::connection("mysql_user")->table("user_device")->insert(["device_id" => $device_id, "device_model" => $device_model, "user_id"=>$user_id]);
            }
            return Response::json(array("code"=>200, "status"=>"succeed"));
        } else {
            abort(404);
        }

    }

    function iapPurchase(Request $request){
        if($request->has(["receipt"]) && $request->only(["receipt"])){
            $user_id = UserCenterController::GetUserId(Cookie::get("token"));
            $receipt = $request->input("receipt");
            $headers = array('content-type:application/vnd.api+json',);
            $ch = curl_init();
            curl_setopt ($ch, CURLOPT_URL, "https://buy.itunes.apple.com/verifyReceipt");
            curl_setopt ($ch, CURLOPT_POST, 1);
            $post_data = '{"receipt-data":"'.$receipt.'"}';
            if($post_data != ''){curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);}
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 120);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $file_contents = curl_exec($ch);
            curl_close($ch);
            $production=(array)json_decode($file_contents,true);
            unset($ch);
            if($production["status"] != 0) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://sandbox.itunes.apple.com/verifyReceipt");
                curl_setopt($ch, CURLOPT_POST, 1);
                $post_data = '{"receipt-data":"' . $receipt . '"}';
                if ($post_data != '') {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                }
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_HEADER, false);
                $file_contents = curl_exec($ch);
                curl_close($ch);
                $sandbox = (array)json_decode($file_contents, true);
                if ($sandbox["status"] != 0) {
                    return Response::json(array("code"=>403, "status"=>"failed", "sandbox" => $sandbox["status"], "prodcution" => $production["status"]));
                } else {
                    DB::connection("mysql_user")->table("user_purchase")->insert(["user_id"=>$user_id, "receipt"=>$receipt,"authorize_data"=>$file_contents,"environment"=>"sandbox","price"=>30]);
                }
            } else {
                DB::connection("mysql_user")->table("user_purchase")->insert(["user_id"=>$user_id, "receipt"=>$receipt,"authorize_data"=>$file_contents,"environment"=>"production","price"=>30]);
            }

            return Response::json(array("code"=>200, "status"=>"succeed"));
        } else {
            abort(404);
        }

    }

    function confirmLoggedIn(){
        $id = UserCenterController::GetUserId(Cookie::get("token"));
        return Response::json(array("code"=>200,"id"=>$id));
    }
}
