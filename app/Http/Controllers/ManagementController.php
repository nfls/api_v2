<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Response;
use Cookie;

class ManagementController extends Controller
{
    const IS_ROOT = 1;
    const MESSAGE_EDIT = 2;
    const MESSAGE_ADMIN = 3;
    function checkPermission($id,$permission){
        return true;
    }

    function getAllMessages(Request $request){
        if ($request->has("startFrom")) {
            $startWith = (int)($request->input("startFrom"));
        } else {
            $startWith = 0;
        }
        $query = DB::connection("mysql_user")->table("system_message")->orderBy("id","desc")->select("id","time","type","receiver","title")->limit(10)->offset($startWith)->get();
        $total = array();

        foreach($query as $single){
            $info = array();
            $info["id"] = $single->id;
            $info["time"] = $single->time;
            $info["type"] = UserCenterController::GetNoticeType($single->type);
            $info["receiver"] = $this->getGropus($single->receiver);
            $info["title"] = $single->title;
            array_push($total,$info);
        }
        Return Response::json(array("code"=>200,"info"=>$total));
    }

    function saveAMessage(Request $request){

        if($request->has(["title","detail","img","site","url","groups","receiver"]) && $this->checkPermission(UserCenterController::GetUserId(Cookie::get("token")),self::MESSAGE_EDIT)){
            $query = DB::connection("mysql_user")->table("system_message");
            $conf = json_encode(array("site"=>$request->input("site"),"url"=>$request->input("url")));
            $array = ["title"=>$request->input("title"),"detail"=>$request->input("detail"),"img"=>$request->input("img"),"conf"=>$conf];
            if($this->checkPermission(UserCenterController::GetUserId(Cookie::get("token")),self::MESSAGE_ADMIN)){
                $array["groups"] = $request->input("groups");
                $array["receiver"] = $request->input("receiver");
            }else{
                $array["groups"] = 0;
                $array["receiver"] = UserCenterController::GetUserId(Cookie::get("token"));
            }
            if($request->has("id") && $request->input("id") > 0){
                $query->where(["id"=>$request->input("id")])->update($array);
            }else{
                $query->insert($array);
            }
            return Response::json(array("code"=>200));
        }
    }
    function getAMessage(Request $request){
        if($request->has("id") && $this->checkPermission(UserCenterController::GetUserId(Cookie::get("token")),self::MESSAGE_EDIT)){
            $result = DB::connection("mysql_user")->table("system_message")->where(["id"=>$request->input("id")])->first();
            return Response::json(array("code"=>200,"info"=>$result));
        }
    }

    function getGropus($id){
        switch ($id){
            case -1:
                return "所有人";
                break;
            case 0:
                return "管理员";
                break;
            default:
                return UserCenterController::GetUserNickname($id);
                break;
        }
    }
}
