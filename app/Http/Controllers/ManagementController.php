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
        $query = DB::connection("mysql_user")->table("system_message")->orderBy("id","asc")->select("id","time","type","receiver","title")->limit(10)->offset($startWith);
        $total = array();

        foreach($query as $single){
            $info = array();
            $info["id"] = $single->id;
            $info["time"] = $single->time;
            $info["type"] = UserCenterController::GetNoticeType($info->type);
            $info["receiver"] = $this->getGropus($info->receiver);
            $info["title"] = $info->title;
            array_push($total,$info);
        }
        Return Response::json(array("code"=>200,"info"=>$total));
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
