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
    const PICTURES_ADMIN = 5;
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
            if($request->input("place") != 3)
                $array["conf"] = json_encode(array("type"=>$request->input("site"),"url"=>$request->input("url")));
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

    function saveAPicture(Request $request){

        if($request->has(["title","url"]) && $this->checkPermission(UserCenterController::GetUserId(Cookie::get("token")),self::MESSAGE_EDIT)){
            $query = DB::connection("mysql_user")->table("app_startup_pics");
            $array = ["text"=>$request->input("title"),"url"=>$request->input("url"),"valid_after"=>$request->input("start"),"invalid_after"=>$request->input("end")];
            if($this->checkPermission(UserCenterController::GetUserId(Cookie::get("token")),self::MESSAGE_ADMIN)){
                if($request->has("receiver") && $request->input("receiver") == -1)
                    $array["receiver"] = -1;
                else
                    $array["receiver"] = 0;
            }else{
                $array["receiver"] = 0;
            }
            if($request->has("id") && $request->input("id") > 0){
                LogController::writeLog("picture.edit","修改了id=".$request->input("id")."的启动图片",1);
                $query->where(["id"=>$request->input("id")])->update($array);
            }else{
                LogController::writeLog("picture.add","添加了title=".$request->input("title")."的启动图片",1);
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

    function getAuthList(Request $request){
        if(!UserCenterController::checkAdmin(UserCenterController::GetUserId(Cookie::get("token")))){
            return "Permission Error!";
        }
        $all = $request->has("all");
        $str = '<table><tr><th>ID</th><th>用户名</th><th>姓名</th><th>班级</th><th>手机号</th><th>推送设备</th></tr>';
        if($all)
            $users = DB::connection("mysql_user")->table("user_list")->where("phone","!=","0")->get();
        else
            $users = DB::connection("mysql_ic")->table("ic_auth")->where(["enabled"=>1])->get();
        $auth = new UserCenterController();
        foreach($users as $user){
            $username = UserCenterController::GetUserNickname($user->id);
            $phone = UserCenterController::GetUserMobile($user->id);
            $info = $auth->ICInfo($user->id,null,null,null,false);
            $name = $info["chnName"];
            $class = $info["tmpClass"];
            $id = $user->id;
            $devices = DB::connection("mysql_user")->table("user_device")->where(["user_id"=>$user->id])->get();
            $model = count($devices);
            if($model == 0)
                $model = "无";
            /*
            foreach($devices as $device){
                $model = $model.$device->device_model."; ";
            }
            */
            $str = $str."<tr><th>$id</th><th>$username</th><th>$name</th><th>$class</th><th>$phone</th><th>$model</th>";
        }
        $str = $str."</table>";
        return $str;
    }

    function getActivityList(){
        if(!UserCenterController::checkAdmin(UserCenterController::GetUserId(Cookie::get("token")))){
            return "Permission Error!";
        }
        $str = '<table><tr><th>ID</th><th>用户名</th><th>姓名</th><th>班级</th><th>手机号</th><th>推送设备</th></tr>';
        $users = DB::connection("mysql_ic")->table("ic_activity")->get();
        $auth = new UserCenterController();
        foreach($users as $user){
            $username = UserCenterController::GetUserNickname($user->user_id);
            $phone = UserCenterController::GetUserMobile($user->user_id);
            $info = $auth->ICInfo($user->user_id);
            $name = $info["chnName"];
            $class = $info["tmpClass"];
            $id = $user->user_id;
            $devices = DB::connection("mysql_user")->table("user_device")->where(["user_id"=>$user->user_id])->get();
            $model = count($devices);
            if($model == 0)
                $model = "无";
            /*
            foreach($devices as $device){
                $model = $model.$device->device_model."; ";
            }
            */
            $str = $str."<tr><th>$id</th><th>$username</th><th>$name</th><th>$class</th><th>$phone</th><th>$model</th>";
        }
        $str = $str."</table>";
        return $str;
    }

    function getTicketInfo(Request $request){
        if(!$request->has("token"))
            abort(403);
        $token = $request->input("token");
        if($request->isMethod("get")){
            $user = DB::connection("mysql_ic")->table("ic_activity")->where(["auth_code"=>$token])->get();
            if(count($user) != 1)
                abort(403);
            $user = $user[0];
            $user_id = $user->user_id;
            $used_time = $user->used_time;
            $auth = new UserCenterController();
            $username = UserCenterController::GetUserNickname($user_id);
            $phone = UserCenterController::GetUserMobile($user_id);
            $info = $auth->ICInfo($user_id);
            $chnName = $info["chnName"];
            $engName = $info["engName"];
            $class = $info["tmpClass"];
            return array("code"=>200,"info"=>array("chnName"=>$chnName,"engName"=>$engName,"class"=>$class,"phone"=>$phone,"username"=>$username,"used_time"=>$used_time));
        }else{
            DB::connection("mysql_ic")->table("ic_activity")->where(["auth_code"=>$token])->update(["used_time"=>date('Y-m-d H:i:s')]);
            return array("code"=>200);
        }
    }
    function safeDecrypt($encrypted, $key)
    {
        $decoded = base64_decode($encrypted);
        $nonce = mb_substr($decoded, 0, \Sodium\CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($decoded, \Sodium\CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

        return \Sodium\crypto_secretbox_open(
            $ciphertext,
            $nonce,
            $key
        );
    }
    function deployGit($content){
        switch($content){
            case "blog_hqy":
                exec("cd /var/www/hqy_blog && git pull && bundler exec jekyll build");
                break;
            case "api":
                exec("cd /var/www/nfls-api && git pull");
                break;
            default:
                break;
        }
    }
}
