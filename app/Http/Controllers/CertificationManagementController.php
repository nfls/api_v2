<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Cookie;
use Illuminate\Pagination\Paginator;
use Response;
use Illuminate\Support\Facades\DB;

class CertificationManagementController extends Controller
{
    function getInstruction(){
        if(!UserCenterController::checkAdmin(Cookie::get("token")))
            abort(403);
        $messages = "注意事项<br/>".
        "1. 索引编号小学格式为四位数入学年份，初高中各式位四位数入学年份+两位数班级号（类似于答题卡学号，但无个人学号），中加班级号A-F转换为对应的1-6。<br/>".
        "2. 索引编号为数组，由半角逗号分隔，每两个索引之间请用一个逗号分割。请不要输入除数字和半角逗号以外的其他内容。<br/>".
        "3. 默认情况下无需修改服务器自动生成的索引编号。普高编号默认为6个，分别代表从高一到高三6个学期的班级号。其他选项默认都为1个索引。<br/>".
        "4. 如果存在病假一年等导致班级号变更的情况，请根据说明补充索引。如某南外初中校友是（2000年入学的）06班学生，病假一年后转到了下一届（2001年入学）的07班，则需要将索引修改为'200006,200107'。<br/>".
        "5. 如果表格存在小问题，可使用编辑功能。只有在大错误的情况下使用拒绝功能，并在消息内注明需要修改的地方。遇到瞎填的用户请直接使用忽略功能。<br/>".
        "6. 消息栏内的文字将作为消息通知的一部分发送给用户，可根据实际情况填写。<br/>".
        "7. 审核历史记录可在日志区查询。";
        return Response::json($messages);
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
                        if($this->isInteger($senior_inter,$return)){
                            $this->sendIdentityMessage("通过",$.cookie("current_id"));
                            return Response::json(array("code"=>"200"));
                        }

        }
        return Response::json(array("code"=>"403","info"=>$return));
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

    function sendIdentityMessage($content,$id){
        DB::connection("mysql_user")->table("system_message")->insert(["type"=>2,"receiver"=>$id,"title"=>"市民认证动态","detail"=>$content,"push_text"=>null]);
    }
}
