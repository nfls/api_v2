<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cookie;
use Response;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{

    //
    function getSubmittedUserList(Request $request){
        if(!UserCenterController::checkAdmin(1))
            abort(403);
        $return = array();
        $temp = array();
        $info = DB::connection('mysql_alumni')->table('user_auth')->whereNull("status_change_time")->whereNotNull("submit_time")->get();
        foreach($info as $user){
            array_push($return,(array("id"=>$user->id) + json_decode($user->auth_info,true) + json_decode($user->primary_school,true) + json_decode($user->junior_school,true) + json_decode($user->senior_school,true)));
        }
        return Response::json($return);
    }

    //function
}
