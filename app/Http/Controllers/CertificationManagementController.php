<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cookie;
use Response;
use Illuminate\Support\Facades\DB;

class CertificationManagementController extends Controller
{

    function getUserDetail(Request $request){
        if(!UserCenterController::checkAdmin(1))
            abort(403);
        $id = $request->input("id");
        $user = DB::connection('mysql_alumni')->table('user_auth')->where(["id"=>$id])->first();
        $return = (json_decode($user->auth_info,true) + json_decode($user->primary_school,true) + json_decode($user->junior_school,true) + json_decode($user->senior_school,true));
        return Response::json($return);

    }
    function getSubmittedUserList(Request $request){
        if(!UserCenterController::checkAdmin(1))
            abort(403);
        $return = array();
        $info = DB::connection('mysql_alumni')->table('user_auth')->whereNull("status_change_time")->whereNotNull("submit_time")->get();
        foreach($info as $user){
            array_push($return, array("submit_time"=>$user->submit_time,"id"=>$user->id,"email"=>json_decode($user->auth_info)->email,"realname"=>json_decode($user->auth_info)->realname));
            //array_push($return,(array("id"=>$user->id)  + json_decode($user->auth_info,true) + array("cut2"=>"") + json_decode($user->primary_school,true) + array("cut3"=>"") + json_decode($user->junior_school,true) + array("cut4"=>"") + json_decode($user->senior_school,true)));
        }
        return Response::json($return);
    }

    function generateIndex(Request $request){
        $id=$request->input("id");
        $user = DB::connection('mysql_alumni')->table('user_auth')->where(["id"=>$id])->first();
        $primary = array();
        if(json_decode($user->primary_school)->primary_school_no != -1){
            $primary = json_decode($user->primary_school)->primary_enter_year;
        }
        $junior = array();
        if(json_decode($user->junior_school)->junior_school_no != -1){
            $junior = json_decode($user->junior_school)->junior_enter_year*100 + json_decode($user->junior_school)->junior_class;
        }
        return Response::json(array("primary"=>$primary,"junior"=>junior));
    }
    //function
}
