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
            echo 2;
            if(UserCenterController::ConfirmCaptcha($request->input("session"), $request->input("captcha"), "nameQuery"))
                return array("status"=>"failure","message"=>"验证码无效或不正确");
            $names = DB::connection("mysql_alumni")->table("students")->where(["name"=>$request->input("name")])->get();
            foreach($names as $name){
            }
            return Response::json($names);
        } else {
            echo 1;
            echo $request->input("name");
            echo $request->input("session");
            echo $request->input("captcha");
            abort(404);
        }
    }
}
