<?php

namespace App\Http\Controllers;

use App\User;
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
            }else{
                DB::connection("mysql_user")->table("user_device")->where(["device_id"=>$device_id])->update(["device_model" => $device_model, "user_id"=>$user_id]);
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
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 120);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $file_contents = curl_exec($ch);
            curl_close($ch);
            $data=(array)json_decode($file_contents,true);
            if($data["status"] != 0) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://sandbox.itunes.apple.com/verifyReceipt");
                curl_setopt ($ch, CURLOPT_POST, 1);
                $post_data = '{"receipt-data":"'.$receipt.'"}';
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 120);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_HEADER, false);
                $file_contents = curl_exec($ch);
                curl_close($ch);
                $data = (array)json_decode($file_contents, true);
                if ($data["status"] != 0) {
                    return Response::json(array("code"=>403, "status"=>"failed"));
                } else {
                    $env = "sandbox";
                }
            } else {
                $env = "production";
            }
            DB::connection("mysql_user")->table("user_purchase")->insert(["user_id"=>$user_id, "receipt"=>$receipt,"authorize_data"=>$file_contents,"environment"=>$env,"price"=>0]);
            $products = $data["receipt"]["in_app"];
            $game = new GameListController();
            foreach($products as $product){
                $id = $product["product_id"];
                $transaction_id = $product["transaction_id"];
                $game->purchaseManager($user_id,$id,$transaction_id,$env);
            }
            return Response::json(array("code"=>200, "status"=>"succeed"));
        } else {
            abort(404);
        }

    }

    function confirmLoggedIn(){
        if(!(stripos($_SERVER['HTTP_USER_AGENT'],'1.2.3')===false) || !(stripos($_SERVER['HTTP_USER_AGENT'],'1.2.6')===false) || !(stripos($_SERVER['HTTP_USER_AGENT'],'1.2.5')===false) || !(stripos($_SERVER['HTTP_USER_AGENT'],'Nflsers-Android')===false)){
            $id = UserCenterController::GetUserId(Cookie::get("token"));
            return Response::json(array("code"=>200,"id"=>$id));
        }else{
            abort(403);
        }

    }

    function getNotice(){
        return "";//Response::json(array("code"=>200,"info"=>array("id"=>200,"title"=>"更新通知","text"=>"新版本客户端已经发布，请在App Store中下载更新！此版本在本周六（16号）凌晨0点将彻底停用。")));
    }

    function compareAuthDatabase(Request $request){
        if($request->only("version") && $request->has("version")){
            $require_version = "1.2.2";
            if(version_compare($request->get("version"),$require_version,">=")){
                return Response::json(array("code"=>200));
            } else {
                return Response::json(array("code"=>304));
            }
        }
    }

    function compareAppVersion(Request $request){
        if($request->only("version") && $request->has("version")){
            $recommand_version = "1.2.5";
            $require_version = "1.2.3";
            if(version_compare($request->get("version"),$recommand_version,">=")){
                return Response::json(array("code"=>200));
            } else if(version_compare($request->get("version"),$require_version,">=")){
                return Response::json(array("code"=>201));
            } else {
                return Response::json(array("code"=>202));
            }
        }
    }

    function compareAndroidAppVersion(Request $request){
        if($request->only("version") && $request->has("version")){
            $recommand_version = "0.1.2";
            $require_version = "0.1.2";
            if(version_compare($request->get("version"),$recommand_version,">=")){
                return Response::json(array("code"=>200));
            } else if(version_compare($request->get("version"),$require_version,">=")){
                return Response::json(array("code"=>201,"info"=>"https://app.nfls.io/android/". $recommand_version .".apk"));
            } else {
                return Response::json(array("code"=>202,"info"=>"https://app.nfls.io/android/". $recommand_version .".apk"));
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

    function pushAMessage(Request $request){
        print("<plaintext>");
        if(!UserCenterController::checkAdmin(UserCenterController::GetUserId(Cookie::get("token")))){
            return "Permission Error!";
        }
        $str = DB::connection("mysql_user")->table("system_message")->where(["place"=>2])->orderBy("id","desc")->first()->detail;
        $list = DB::connection("mysql_user")->table("user_device")->get();
        $push = new ApnsPHP_Push(
            ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION,
            '/etc/cert/push.pem'
        );
        $push->setRootCertificationAuthority('/etc/cert/entrust.pem');
        foreach ($list as $device){
            $message = new ApnsPHP_Message($device->device_id);
            $message->setText($str);
            $message->setSound();
            $push->add($message);
        }
        $push->connect();
        $push->send();
        $push->disconnect();

        $push = new ApnsPHP_Push(
            ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
            '/etc/cert/push.pem'
        );
        foreach ($list as $device){
            $message = new ApnsPHP_Message($device->device_id);
            $message->setText($str);
            $message->setSound();
            $push->add($message);
        }
        $push->connect();
        $push->send();
        $push->disconnect();
        print("</plaintext");
    }


}
