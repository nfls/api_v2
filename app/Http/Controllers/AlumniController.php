<?php

namespace App\Http\Controllers;

use App\Http\Controllers\UserCenterController;
use Illuminate\Http\Request;
use Cookie;
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

    function AuthUpdate(Request $request,$step){
        if(is_numeric($step)==true){
            $id = UserCenterController::GetUserId(Cookie::get('token'));
            //$action = $request->input('action');
            $user = DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->first();
            if(is_null($user))
                self::InsertId($user);
            $content = file_get_contents('php://input');
            $return_array = self::StepCheck($step,$content);
            if(count($return_array)!=1)
            {
                return Response::json(array('code' => '403.1' , 'message' => $return_array));

            }
            else
            {
                DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->update(['junior_school' => $content]);
                return Response::json(array('code' => '200'));
            }
            return Response::json($return_array);
        }
    }

    function AuthQuery(Request $request,$step)
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
                abort('404','hahaha');
        }

        $return_array = array();
        $return_array['id'] = $id;
    }


    function StepCheck($step,$content)
    {
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
                if(@self::isEmpty($info->{'primary_school'}))
                    array_push($message,'请选择您所就读的小学。');
                else
                {
                    switch($info->primary_school)
                    {
                        case self::OTHER_PRIMARY:
                            if(@self::isEmpty($info->{'primary_school_name'}))
                                array_push($message, '请填写您所就读的小学全名。请不要使用任何简写。');
                            break;
                        case self::NFLS_PRIMARY:
                            $passed = true;
                            if(@self::isEmpty($info->{'primary_school_enter_year'}))
                            {
                                array_push($message, '请填写您小学的入学年份。');
                                $passed = false;
                            }
                            if(@self::isEmpty($info->{'primary_school_graduated_year'}))
                            {
                                array_push($message, '请填写您小学的毕业年份。'); 
                                $passed = false;
                            }
                            if($passed)
                            {
                                $valid  = new Between(['min' => 1963, 'max' => 1983]);//enter before 1979
                                if(!$valid->isValid($info->{'primary_school_enter_year'}) || !is_integer($info->{'primary_school_enter_year'}))
                                    array_push($message, '小学入学年份不正确！请检查您的入学年份。此项仅支持1979年之前入学校友填写，并请不要输入非数字内容。');
                                if(!$valid->isValid($info->{'primary_school_graduated_year'}) || !is_integer($info->{'primary_school_graduated_year'}))
                                    array_push($message, '小学毕业年份不正确！请检查您的毕业年份。此项仅支持1979年之前入学校友填写，并请不要输入非数字内容。');
                                if($info->{'primary_school_enter_year'} + 4 != $info->{'primary_school_graduated_year'} && (@self::isEmpty($info->{'remark'})))
                                    array_push($message, '小学毕业年份与入学年份不对应！如果有特殊情况，请在备注中注明。');
                            }
                            break;
                        default:
                            array_push($message,'小学信息不正确！请重新选择。');
                            break;
                    }
                }
                break;
            case self::JUNIOR_INFO:
                /*
                    JSON格式：
                        junior_school：学校id
                        junior_school_name：学校全名
                        junior_school_enter_year：入学年份
                        junior_school_graduated_year：毕业年份
                        junior_class：班级号
                        remark：备注
                */
                if(@self::isEmpty($info->{'junior_school'}))
                    array_push($message,'请选择您所就读的初中。');
                else
                {
                    switch($info->junior_school)
                    {
                        case self::OTHER_JUNIOR:
                            if(@self::isEmpty($info->{'junior_school_name'}))
                                array_push($message, '请填写您所就读的初中全名。请不要使用任何简写。');
                            if(count((array)$info)!=3)
                                array_push($message, '您提交的信息存在结构性问题，请重试或解决上面提到的任何错误。如果此错误持续发生，请联系管理员。');
                            break;
                        case self::NFLS_JUNIOR:
                            $passed = true;
                            if(@self::isEmpty($info->{'junior_school_enter_year'}))
                            {
                                array_push($message, '请填写您初中的入学年份。');
                                $passed = false;
                            }
                            if(@self::isEmpty($info->{'junior_school_graduated_year'}))
                            {
                                array_push($message, '请填写您初中的毕业年份。'); 
                                $passed = false;
                            }
                            if(@self::isEmpty($info->{'junior_class'}))
                            {
                                array_push($message, '请填写您初中的班级号。'); 
                                $passed = false;
                            }
                            if($passed)
                            {
                                $valid  = new Between(['min' => 1963, 'max' => date('Y') - 6]);
                                if(!$valid->isValid($info->{'junior_school_enter_year'}) || !is_int($info->{'junior_school_enter_year'}))
                                    array_push($message, '初中入学年份不正确！请检查您的入学年份。目前允许的最大年份为'.(String)(date('Y') - 6).'年');
                                unset($valid);
                                $valid  = new Between(['min' => 1963, 'max' => date('Y') - 3]);
                                if(!$valid->isValid($info->{'junior_school_graduated_year'}) || !is_integer($info->{'junior_school_graduated_year'}))
                                    array_push($message, '初中毕业年份不正确！请检查您的毕业年份。目前允许的最大年份为'.(String)(date('Y') - 3).'年');
                                if($info->{'junior_school_enter_year'} + 3 != $info->{'junior_school_graduated_year'} && (@self::isEmpty($info->{'remark'})))
                                    array_push($message, '初中毕业年份与入学年份不对应！如果有特殊情况，请在备注中注明。');
                                unset($valid);
                                $valid = new Between(['min' => 1, 'max' => 12]);
                                if(!$valid->isValid($info->{'junior_class'}) || !is_integer($info->{'junior_class'}))
                                    array_push($message, '初中班级号不正确！请检查您的初中班级号是否为1-12之间的任何一个整数。');
                            }
                            if(count((array)$info)!=5)
                                array_push($message, '您提交的信息存在结构性问题，请重试或解决上面提到的任何错误。如果此错误持续发生，请联系管理员。');
                            break;
                        default:
                            array_push($message,'初中信息不正确！请重新选择。');
                            break;
                    }
                }
                break;
            case SENIOR_INFO:
                /*
                    JSON格式：
                        senior_school：学校id
                        senior_school_name：学校全名
                        senior_school_enter_year：入学年份
                        senior_school_graduated_year：毕业年份
                        senior_class：班级号
                        remark：备注
                */
                if(@self::isEmpty($info->{'senior_school'}))
                    array_push($message,'请选择您所就读的高中。');
                else
                {
                    switch($info->senior_school)
                    {
                        case self::OTHER_SENIOR:
                            if(@self::isEmpty($info->{'senior_school_name'}))
                                array_push($message, '请填写您所就读的高中全名。请不要使用任何简写。');
                            if(count((array)$info)!=3)
                                array_push($message, '您提交的信息存在结构性问题，请重试或解决上面提到的任何错误。如果此错误持续发生，请联系管理员。');
                            break;
                        case self::NFLS_SENIOR_GENERAL:
                            $passed = true;
                            if(@self::isEmpty($info->{'senior_school_enter_year'}))
                            {
                                array_push($message, '请填写您高中的入学年份。');
                                $passed = false;
                            }
                            if(@self::isEmpty($info->{'senior_school_graduated_year'}))
                            {
                                array_push($message, '请填写您高中的毕业年份。'); 
                                $passed = false;
                            }
                            if(@self::isEmpty($info->{'senior_class'}))
                            {
                                array_push($message, '请填写您高中的班级号。'); 
                                $passed = false;
                            }
                            if($passed)
                            {
                                $valid  = new Between(['min' => 1963, 'max' => date('Y') - 6]);
                                if(!$valid->isValid($info->{'senior_school_enter_year'}) || !is_int($info->{'senior_school_enter_year'}))
                                    array_push($message, '高中入学年份不正确！请检查您的入学年份。目前允许的最大年份为'.(String)(date('Y') - 6).'年');
                                unset($valid);
                                $valid  = new Between(['min' => 1963, 'max' => date('Y') - 3]);
                                if(!$valid->isValid($info->{'senior_school_graduated_year'}) || !is_integer($info->{'senior_school_graduated_year'}))
                                    array_push($message, '高中毕业年份不正确！请检查您的毕业年份。目前允许的最大年份为'.(String)(date('Y') - 3).'年');
                                if($info->{'senior_school_enter_year'} + 3 != $info->{'senior_school_graduated_year'} && (@self::isEmpty($info->{'remark'})))
                                    array_push($message, '高中毕业年份与入学年份不对应！如果有特殊情况，请在备注中注明。');
                                unset($valid);
                                $valid = new Between(['min' => 1, 'max' => 12]);
                                if(!$valid->isValid($info->{'senior_class'}) || !is_integer($info->{'senior_class'}))
                                    array_push($message, '高中班级号不正确！请检查您的高中班级号是否为1-12之间的任何一个整数。');
                            }
                            if(count((array)$info)!=5)
                                array_push($message, '您提交的信息存在结构性问题，请重试或解决上面提到的任何错误。如果此错误持续发生，请联系管理员。');
                            break;
                        case NFLS_SENIOR_AUSTRALIA:
                        case NFLS_SENIOR_BCA:
                        case NFLS_SENIOR_ALEVEL:
                        case NFLS_SENIOR_IB:
                        default:
                            array_push($message,'高中信息不正确！请重新选择。');
                            break;
                    }
                }
                break;
            default:
                array_push($meaasge,'route error');
                break;

        }
        if(empty($message))
            array_push($message,'恭喜您，所有当前的数据均符合要求！');
        else
            array_unshift($message, '非常抱歉，您提交的数据在以下部分存在问题：');
        
        /*
        switch($step){
            case 1:
                if(isset($info->))
        }
        */
        return $message;
    }

    function isEmpty($content)
    {
        return @(is_null($content)|| empty($content));
    }

    function InsertId($id){
        DB::connection('mysql_alumni')->table('user_auth')->insert(['id' => $id]);
    }

    
    
}
