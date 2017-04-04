<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserCenterController extends Controller
{
    public static function GetUserId($token){
    	$user = DB::connection("mysql_user")->table("user_list")->where("token", $token)->first();
    	if(is_null($user))
    		return -1;
    	return $user->id;
    }

    public static function GetUserNickname($id){
    	$user = DB::connection("mysql_forum")->table("nfls_users")->where("id", $id)->first();
    	return $user->username;
    }

    public static function GetUserEmail($id){
    	$user = DB::connection("mysql_forum")->table("nfls_users")->where("id", $id)->first();
    	return $user->email;
    }

        
        
    }
}
