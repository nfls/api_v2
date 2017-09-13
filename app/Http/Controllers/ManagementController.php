<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Response;
use Cookie;

class ManagementController extends Controller
{
    const IS_ROOT = 0;
    const MESSAGE_ADD = 1;
    const MESSAGE_EDIT = 2;
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

    function getAMessage(Request $request){
        if($request->has("id") && $this->checkPermission(UserCenterController::GetUserId(Cookie::get("token")),self::MESSAGE_ADD)){
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
