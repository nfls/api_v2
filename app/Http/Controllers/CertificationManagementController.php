<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cookie;
use Illuminate\Pagination\Paginator;
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
        $return = array();
        if(!UserCenterController::checkAdmin(Cookie::get("token")))
            abort(403);
        $primary = json_decode("[".$request->input("primary")."]",true);
        $junior = json_decode("[".$request->input("junior")."]",true);
        $senior_general = json_decode("[".$request->input("senior_general")."]",true);
        $senior_inter = json_decode("[".$request->input("senior_inter")."]",true);
        if(is_null($primary) ||is_null($junior) ||is_null($senior_general) ||is_null($senior_inter))
            array_push($return, "数据结构问题，请检查索引中是否包含了除半角逗号和数字以外的其他符号。");
        else{
            if($this->isInteger($primary,$return))
                if($this->isInteger($junior,$return))
                    if($this->isInteger($senior_general,$return,6))
                        if($this->isInteger($senior_inter,$return))
                            array_push($return,"检查通过！");
        }
        return Response::json($return);
    }

    function isInteger($data,&$array,$num = -1){
        $count = 0;
        foreach($data as $index){
            if((int)$index != $index){
                array_push($array, "请检查索引是否为纯数字！");
                return false;
            }
            $count ++;
        }
        if($num != -1 && $num != $count && $count != 0){
            array_push($array, "请检查索引是否完整！");
            return false;
        }
        return true;
    }
    //function
}
