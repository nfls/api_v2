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
    const PICTURES_EDIT = 4;
    function checkPermission($id,$bit){
        $permission = DB::connection("mysql_user")->table("user_list")->where(["id"=>$id])->first()->permissions;
        return $permission & (1<<$bit);
    }

    function getAllMessages(Request $request){
        if($this->checkPermission(UserCenterController::GetUserId(Cookie::get("token")),self::MESSAGE_EDIT)){
            if ($request->has("startFrom")) {
                $startWith = (int)($request->input("startFrom"));
            } else {
                $startWith = 0;
            }
            $query = DB::connection("mysql_user")->table("system_message")->orderBy("id","desc")->select("id","time","type","receiver","title","place")->limit(10)->offset($startWith);
            if($request->has("place") && $request->input("place")!=0){
                $query = $query->where(["place"=>$request->input("place")]);
            }
            $query = $query->get();
            $total = array();

            foreach($query as $single){
                $info = array();
                $info["id"] = $single->id;
                $info["time"] = $single->time;
                $info["type"] = UserCenterController::GetNoticeType($single->type);
                $info["receiver"] = $this->getGropus($single->receiver);
                $info["title"] = $single->title;
                $info["place"] = $single->place;
                array_push($total,$info);
            }
            Return Response::json(array("code"=>200,"info"=>$total));
        }
    }

    function getAllPictures(Request $request){
        if($this->checkPermission(UserCenterController::GetUserId(Cookie::get("token")),self::PICTURES_EDIT)){
            if ($request->has("startFrom")) {
                $startWith = (int)($request->input("startFrom"));
            } else {
                $startWith = 0;
            }
            $query = DB::connection("mysql_user")->table("app_startup_pics")->orderBy("id","desc")->limit(10)->offset($startWith)->get();
            $total = array();

            foreach($query as $single){
                $info = array();
                $info["id"] = $single->id;
                $info["time"] = $single->update_time;
                $info["receiver"] = $this->getGropus($single->groups);
                $info["text"] = $single->text;
                $info["start"] = $single->valid_after;
                $info["end"] = $single->invalid_after;
                array_push($total,$info);
            }
            Return Response::json(array("code"=>200,"info"=>$total));
        }
    }

    function saveAMessage(Request $request){

        if($request->has(["title","detail","place","type"]) && $this->checkPermission(UserCenterController::GetUserId(Cookie::get("token")),self::MESSAGE_EDIT)){
            $query = DB::connection("mysql_user")->table("system_message");
            $array = ["title"=>$request->input("title"),"detail"=>$request->input("detail"),"type"=>$request->input("type")];
            if($request->input("place") == 3){
                if($request->has("img"))
                    $array["img"] = $request->input("img");
                else
                    abort(403);
            }
            $array["place"] = $request->input("place");
            if($request->has("site") && $request->input("site") != "none")
                $array["conf"] = json_encode(array("site"=>$request->input("site"),"url"=>$request->input("url"),"place"=>$request->input("place")));
            else if($request->has("url")){
                $array["conf"] = $request->input("url");
            }
            if($this->checkPermission(UserCenterController::GetUserId(Cookie::get("token")),self::MESSAGE_ADMIN)){
                if($request->has("receiver"))
                    $array["receiver"] = $request->input("receiver");
                else
                    $array["receiver"] = UserCenterController::GetUserId(Cookie::get("token"));
            }else{
                $array["receiver"] = UserCenterController::GetUserId(Cookie::get("token"));
            }
            if($request->has("id") && $request->input("id") > 0){
                LogController::writeLog("message.edit","修改了id=".$request->input("id")."的消息",1);
                $query->where(["id"=>$request->input("id")])->update($array);
            }else{
                LogController::writeLog("message.add","添加了title=".$request->input("title")."的消息",1);
                $query->insert($array);
            }
            return Response::json(array("code"=>200));
        }
    }

    function deleteAMessage(Request $request){
        if($request->has("id") && $this->checkPermission(UserCenterController::GetUserId(Cookie::get("token")),self::MESSAGE_ADMIN)){
            DB::connection("mysql_user")->table("system_message")->where(["id"=>$request->input("id")])->delete();
            LogController::writeLog("message.delete","删除了id=".$request->input("id")."的消息",1);
            return Response::json(array("code"=>200));
        }
    }

    function getPermission(){
        $id = UserCenterController::GetUserId(Cookie::get("token"));
        if($this->checkPermission($id,self::MESSAGE_ADMIN)){
            $info = "您拥有本页上所有内容的管理权限";
        }else if($this->checkPermission($id,self::MESSAGE_EDIT)){
            $info = "您拥有添加及修改通知的权限，但是新通知只能发送给自己";
        }else{
            $info = "您没有任何权限";
        }
        return Response::json(array("code"=>200,"info"=>$info));
    }

    function getAMessage(Request $request){
        if($request->has("id") && $this->checkPermission(UserCenterController::GetUserId(Cookie::get("token")),self::MESSAGE_EDIT)){
            $result = DB::connection("mysql_user")->table("system_message")->where(["id"=>$request->input("id")])->first();
            return Response::json(array("code"=>200,"info"=>$result));
        }
    }

    function getAPicture(Request $request){
        if($request->has("id") && $this->checkPermission(UserCenterController::GetUserId(Cookie::get("token")),self::PICTURES_EDIT)){
            $single = DB::connection("mysql_user")->table("app_startup_pics")->where(["id"=>$request->input("id")])->first();
            $info = array();
            $info["id"] = $single->id;
            $info["time"] = $single->update_time;
            $info["receiver"] = $single->groups;
            $info["text"] = $single->text;
            $info["start"] = $single->valid_after;
            $info["end"] = $single->invalid_after;
            $info["url"] = $single->url;
            return Response::json(array("code"=>200,"info"=>$info));
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

    function uploadFile(Request $request){
        if(UserCenterController::checkAdmin(UserCenterController::GetUserId(Cookie::Get("token")))){
            $path = $request->file('file')->store('public');
            LogController::writeLog("file.upload","上传了名称为".$request->file('file')->getClientOriginalName()."的新文件，已存储于".$path,1);
            return "https://api.nfls.io/storage" . substr($path,6);
        }

    }

    function uploadPage(){
        return view("upload");
    }
}
