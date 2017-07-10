<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cookie;
use Response;
use Illuminate\Support\Facades\DB;

class CertificationManagementController extends Controller
{

    function getUserDetail(Request $request){

    }
    function getSubmittedUserList(Request $request){
        if(!UserCenterController::checkAdmin(1))
            abort(403);
        $return = array();
        $temp = array();
        $info = DB::connection('mysql_alumni')->table('user_auth')->whereNull("status_change_time")->whereNotNull("submit_time")->get();
        $cut = array("cut"=>"");
        foreach($info as $user){
            array_push($return, array("submit_time"=>$user->submit_time,"id"=>$user->id));
            //array_push($return,(array("id"=>$user->id)  + json_decode($user->auth_info,true) + array("cut2"=>"") + json_decode($user->primary_school,true) + array("cut3"=>"") + json_decode($user->junior_school,true) + array("cut4"=>"") + json_decode($user->senior_school,true)));
        }
        return Response::json($return);
    }

    //function
}
