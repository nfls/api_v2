<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Cookie;
use Illuminate\Pagination\Paginator;
use Response;
use Illuminate\Support\Facades\DB;

class StudentsListController extends Controller
{
    function getNameList(Request $request){
        if($request->has(["name","session","captcha"] && $request->only(["name","session","captcha"]))){
            if(UserCenterController::ConfirmCaptcha($request->get("session"), $request->get("captcha"), "nameQuery"))
                return array("status"=>"failure","message"=>"验证码无效或不正确");
            $names = DB::connection("mysql_alumni")->table("students")->where(["name"=>$request->get("name")])->get();
            foreach($names as $name){
            }
            return Response::json($names);
        } else {
            echo $request->get("name");
            echo $request->get("session");
            echo $request->get("captcha");
            abort(404);
        }
    }
}
