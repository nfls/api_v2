<?php

namespace App\Http\Controllers;

use App\Http\Controllers\UserCenterController;
use Illuminate\Http\Request;;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Routing\ResponseFactory;
use Zend\Validator\Between;
use Response;

class AlumniController extends Controller
{
    const NOT_START = 0;
    const IN_PROGRESS = 1;
    const FINISHED = 2;

    function StepCheck($step,$content){
        $info = json_decode($content);
        switch($step){
            case 1:
                break;
        }
        if(!isset($info->real_name) || !is_($info->))
            return false;
        /*
        switch($step){
            case 1:
                if(isset($content->))
        }
        */
        return true;
    }

    function InsertId($id){
        DB::connection("mysql_alumni")->table("user_auth")->insert('insert into student (name) values(?)',[$id]);
    }

    function auth(Request $request,$step){
    	if(is_numeric($step)==true){
    		$id = UserCenterController::GetUserId($request->input("token"));
            $user = DB::connection("mysql_alumni")->table("user_auth")->where("id", $id)->first();
            if(is_null($user))
                self::InsertId($user);
            $return_array = array();
            $return_array['id'] = $id;
    		switch($step){
    			case 1:
                    $return_array['username'] = UserCenterController::GetUserNickname($id);
                    $return_array['email'] = UserCenterController::GetUserEmail($id);
                    if(is_null($user->personal_info))
                        $return_array['status'] = self::NOT_START;
                    else if (self::StepCheck(1,$user->personal_info)==true)
                        $return_array['status'] = self::FINISHED;
                    else $return_array['status'] = self::IN_PROGRESS;
    				break;
    			case 2:
    				break;
    			case 3:
    				break;
    			case 4:
    				break;
    			case 5:
    				break;
    			case 6:
    				break;
    			case 7:
    				break;
    			default:
    				abort("404","hahaha");
    		}
            return Response::json($return_array);
    	}
	}

    
}
