<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Response;
use Illuminate\Support\Facades\DB;

class AlumniWebsiteController extends Controller
{

    static function getUser($token)
    {
        if(Cookie::get("admin") == "true" && !is_null(Cookie::get("current_id"))){
            if(UserCenterController::checkAdmin(UserCenterController::GetUserId(Cookie::get("token")))){
                if(UserCenterController::isUserExist(Cookie::get("current_id"))){
                    $id = Cookie::get("current_id");
                } else {
                    abort(403);
                }
            } else {
                abort(403);
            }
        } else {
            $id = UserCenterController::GetUserId($token);
            if ($id < 0) {
                abort(403);
                return false;
            }
        }

        $user = DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->first();
        if (is_null($user))
            self::InsertId($id);
        return $id;
    }

    function getPostList(){
        return Response::json(array("code"=>200,"info"=>DB::connection("mysql_alumni")->table("typecho_contents")->where(["type"=>"post"])->select('cid','title','modified')->get()));
    }

    function getDetailPost(Request $request){
        if($request->has("cid") && $request->only("cid")){
            return Response::json(array("code"=>200,"info"=>DB::connection("mysql_alumni")->table("typecho_contents")->where(["type"=>"post","cid"=>$request->input("cid")])->select('text')->get()));
        }
    }
}
