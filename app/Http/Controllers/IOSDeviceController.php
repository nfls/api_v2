<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cookie;
use Illuminate\Pagination\PaginationServiceProvider;
use Response;
use Illuminate\Support\Facades\DB;
use ApnsPHP_Push;
use ApnsPHP_Abstract;
use ApnsPHP_Message;

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

    function getNotice(){
        return Response::json(array("code"=>200,"info"=>array("id"=>200,"title"=>"更新通知","text"=>"新版本客户端已经发布，请在App Store中下载更新！此版本在本周六（16号）凌晨0点将彻底停用。")));
    }

    function compareAuthDatabase(Request $request){
        if($request->only("version") && $request->has("version")){
            $require_version = "1.1.1";
            if(version_compare($request->get("version"),$require_version,">=")){
                return Response::json(array("code"=>200));
            } else {
                return Response::json(array("code"=>304));
            }
        }

    }

    function getStartUpPictures(){
        $query = DB::connection("mysql_user")->table("app_startup_pics")->where(function($query){
            $query->where("invalid_after", ">", date('Y-m-d H:i:s'))->orWhere("invalid_after","=",null);
        })->where(function($query){
            $query->where("valid_after", "<", date('Y-m-d H:i:s'))->orWhere("valid_after","=",null);
        });
        if(UserCenterController::checkAdmin(UserCenterController::GetUserId(Cookie::get("token")))){
            $query = $query->where(function($query){
                $query->where(["groups"=>-1])->orWhere(["groups"=>0]);
            });
        } else {
            $query = $query->where(["groups"=>-1]);
        }
        $response = $query->orderBy("id","desc")->first();
        $array["code"] = 200;
        $array["info"]["id"] = $response->id;
        $array["info"]["text"] = $response->text;
        $array["info"]["url"] = $response->url;
        $array["info"]["invalid_after"] = $response->invalid_after;
        return Response::json($array);
    }

    function pushAMessage(){
        $list = DB::connection("mysql_user")->table("user_device")->get();
        $push = new ApnsPHP_Push(
            ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION,
            '/etc/cert/push.pem'
        );
        $push->setRootCertificationAuthority('/etc/cert/entrust.pem');
        foreach ($list as $device){
            $message = new ApnsPHP_Message($device->device_id);
            $message->setCustomIdentifier("Message-Badge-3");
            $message->setBadge(3);
            $message->setText('校友会功能正式开始测试，详情请关注南外校友会微信公众号（校友会建站管理团队）。另，下一版本起，部分页面将更换为英文。');
            $message->setSound();
            $message->setExpiry(30);
            $push->add($message);
        }
        $push->connect();
        $push->send();
        $push->disconnect();
    }
}
