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

    const NFLS_PRIMARY = 1;
    const OTHER_PRIMARY = -1;

    const NFLS_JUNIOR = 1;
    const OTHER_JUNIOR = -1;
    
    const NFLS_SENIOR_GENERAL = 1;
    const NFLS_SENIOR_IB = 2;
    const NFLS_SENIOR_ALEVEL = 3;
    const NFLS_SENIOR_BCA = 4;
    const NFLS_SENIOR_AUSTRALIA = 5; //No longer exist after 2010
    const OTHER_SENIOR = -1;

    const BASIC_INFO = 1;
    const PRIMARY_INFO = 2;
    const JUNIOR_INFO = 3;
    const SENIOR_INFO = 4;
    //N
    const QUERY = 0;
    const UPDATE = 1;
    function StepCheck($step,$content){
        $info = json_decode($content);
        $message = array();
        switch($step){
            case self::BASIC_INFO:
                //if(!isset($info->real_name) || !is_($info->))
                break;
            case self::PRIMARY_INFO:
                /*
                    JSON格式：
                        primary_school：学校id
                        primary_school_name：学校全名
                        primary_school_enter_year：入学年份
                        primary_school_graduated_year：毕业年份
                        remark：备注
                */
                if(@self::EmptyCheck($info->{'primary_school'}))
                    array_push($message,"请选择您所就读的小学。");
                else
                {
                    switch($info->primary_school)
                    {
                        case self::OTHER_PRIMARY:
                            if(@self::EmptyCheck($info->{'primary_school_name'}))
                                array_push($message, "请填写您所就读的小学全名。请不要使用任何简写。");
                            break;
                        case self::NFLS_PRIMARY:
                            $passed = true;
                            if(@self::EmptyCheck($info->{'primary_school_enter_year'}))
                            {
                                array_push($message, "请填写您小学的入学年份。");
                                $passed = false;
                            }
                            if(@self::EmptyCheck($info->{'primary_school_graduated_year'}))
                            {
                                array_push($message, "请填写您小学的毕业年份。"); 
                                $passed = false;
                            }
                            if($passed)
                            {
                                $valid  = new Between(['min' => 1963, 'max' => 1983]);//enter before 1979
                                if(!$valid->isValid($info->{'primary_school_enter_year'}) || !is_numeric($info->{'primary_school_enter_year'}))
                                    array_push($message, "小学入学年份不正确！请检查您的入学年份。此项仅支持1979年之前入学校友填写，并请不要输入非数字内容。");
                                if(!$valid->isValid($info->{'primary_school_graduated_year'}) || !is_numeric($info->{'primary_school_graduated_year'}))
                                    array_push($message, "小学毕业年份不正确！请检查您的毕业年份。此项仅支持1979年之前入学校友填写，并请不要输入非数字内容。");
                                if($info->{'primary_school_enter_year'} + 4 != $info->{'primary_school_graduated_year'} && (@self::EmptyCheck($info->{'remark'})))
                                    array_push($message, "小学毕业年份与入学年份不对应！如果有特殊情况，请在备注中注明。");
                            }
                            break;
                        default:
                            array_push($message,"小学信息不正确！请重新选择。");
                            break;
                    }
                }
                break;
            default:
                array_push($meaasge,"route error");
                break;

        }
        if(empty($message))
            array_push($message,"恭喜您，所有当前的数据均符合要求！");
        else
            array_unshift($message, "非常抱歉，您提交的数据在以下部分存在问题：");
        
        /*
        switch($step){
            case 1:
                if(isset($info->))
        }
        */
        return $message;
    }

    function EmptyCheck($content)
    {
        return @(is_null($content)|| empty($content));
    }

    function InsertId($id){
        DB::connection("mysql_alumni")->table("user_auth")->insert('insert into student (name) values(?)',[$id]);
    }

    function auth(Request $request,$step,$method){
    	if(is_numeric($step)==true){
    		$id = UserCenterController::GetUserId($request->input("token"));
            //$action = $request->input("action");
            $user = DB::connection("mysql_alumni")->table("user_auth")->where("id", $id)->first();
            if(is_null($user))
                self::InsertId($user);

            if($method == "query")
            {
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

                $return_array = array();
                $return_array['id'] = $id;
            }
            else if($method == "update")
            {
                $content = file_get_contents("php://input");
                $return_array = self::StepCheck($step,$content);
            }
            else 
            {
                abort(404,"What f0ck is that?");
            }
            
    		
            return Response::json($return_array);
    	}
	}

    
}
