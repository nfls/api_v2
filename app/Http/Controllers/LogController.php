<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Cookie;

class LogController extends Controller
{
    //
    static function writeLog($operation,$message,$level = 0,$id = 0){
        if($id == 0)
            $id = UserCenterController::GetUserId(Cookie::get("token"));
        $info = "[" . $operation ."]" . $message;
        DB::connection("mysql_user")->table("user_logs")->insert(["userid"=>$id,"info"=>$info,"ip"=>$_SERVER['REMOTE_ADDR'],"level"=>$level]);
    }
}
