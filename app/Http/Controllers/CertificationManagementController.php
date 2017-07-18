<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cookie;
use Response;
use Illuminate\Support\Facades\DB;

class CertificationManagementController extends Controller
{
    function getInstruction(){
        $messages = ["注意事项："];
    }
    function getUserDetail(Request $request){
        if(!UserCenterController::checkAdmin(Cookie::get("token")))
            abort(403);
        $id = $request->input("id");
        $user = DB::connection('mysql_alumni')->table('user_auth')->where(["id"=>$id])->first();
        $return = (json_decode($user->auth_info,true) + json_decode($user->primary_school,true) + json_decode($user->junior_school,true) + json_decode($user->senior_school,true));
        return Response::json($return);

    }
    function getSubmittedUserList(Request $request){
        if(!UserCenterController::checkAdmin(Cookie::get("token")))
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
            array_push($primary,json_decode($user->primary_school)->primary_school_enter_year);
        }
        $junior = array();
        if(json_decode($user->junior_school)->junior_school_no != -1){
            array_push($junior,json_decode($user->junior_school)->junior_school_enter_year*100 + json_decode($user->junior_school)->junior_class);
        }
        $senior_inter = array();
        if(json_decode($user->senior_school)->senior_school_no > 1){
            array_push($senior_inter, json_decode($user->senior_school)->senior_school_enter_year*100 + json_decode($user->senior_school)->senior_class);
        }
        $senior_general = array();
        if(json_decode($user->senior_school)->senior_school_no == 1){
            array_push($senior_general,json_decode($user->senior_school)->senior_school_enter_year*100 + json_decode($user->senior_school)->senior_class_11);
            array_push($senior_general,json_decode($user->senior_school)->senior_school_enter_year*100 + json_decode($user->senior_school)->senior_class_12);
            array_push($senior_general,json_decode($user->senior_school)->senior_school_enter_year*100 + json_decode($user->senior_school)->senior_class_21);
            array_push($senior_general,json_decode($user->senior_school)->senior_school_enter_year*100 + json_decode($user->senior_school)->senior_class_22);
            array_push($senior_general,json_decode($user->senior_school)->senior_school_enter_year*100 + json_decode($user->senior_school)->senior_class_31);
            array_push($senior_general,json_decode($user->senior_school)->senior_school_enter_year*100 + json_decode($user->senior_school)->senior_class_32);
        }
        return Response::json(array("primary"=>$primary,"junior"=>$junior,"senior_general"=>$senior_general,"senior_inter"=>$senior_inter));
    }

    function acceptIdentity(Request $request){
        if(!UserCenterController::checkAdmin(Cookie::get("token")))
            abort(403);
        $primary_index = json_decode("[".$request->input("primary")."]",true);
        var_dump($primary_index);
        var_dump(json_decode("[".$request->input("primary")."=]",true));
        foreach($primary_index as $index){

        }
        json_decode("[".$request->input("junior")."]",true);

        json_decode("[".$request->input("senior_general")."]",true);
        json_decode("[".$request->input("senior_inter")."]",true);
        //$request->input
    }
    //function
}
