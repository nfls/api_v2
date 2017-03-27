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
    const NO_SENIOR = -2;

    const BASIC_INFO = 1;
    const PRIMARY_INFO = 2;
    const JUNIOR_INFO = 3;
    const SENIOR_INFO = 4;

    const SCHOOL_NO = 1;
    const ENTER_YAER = 2;
    const GRADUATED_YEAR = 3;
    
    //const ERR_STRUCTURE =  TO-DO: Add language
    function AuthUpdate(Request $request,$step){
        if(is_numeric($step)==true){
            $id = UserCenterController::GetUserId(Cookie::get('token'));
            $user = DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->first();
            if(is_null($user))
                self::InsertId($id);
            $content = file_get_contents('php://input');
            $info = json_decode($content);
            if(!is_object($info))
                abort("404","Check your input!");
            switch($step)
            {
                case self::BASIC_INFO:

                case self::PRIMARY_INFO:
                    $message = self::AuthStep2($info);
                    return self::DataCheck($message,$id,$content,'primary_info');
                    break;
                case self::JUNIOR_INFO:
                    $message = self::AuthStep3($info);
                    return self::DataCheck($message,$id,$content,'junior_info');
                    break;
                case self::SENIOR_INFO:
                    $message = self::AuthStep4($info);
                    return self::DataCheck($message,$id,$content,'senior_info');
                    break;
            }
        }
    }

    function AuthQuery(Request $request,$step)
    {
        switch($step){
            default:
                break;
        }

        $return_array = array();
        $return_array['id'] = $id;
    }

    function DataCheck($message,$name,$id,$content,$insert=true)
    {
        if(empty($message))
        {
            array_push($message,'恭喜您，所有当前的数据均符合要求！');
            if($insert)
                DB::connection('mysql_alumni')->table($name)->where('id', $id)->update(['junior_school' => $content]);
            return Response::json(array('code' => '200', 'message' => $message));
        }
        else
        {
            array_unshift($message, '非常抱歉，您提交的数据在以下部分存在问题：');
            return Response::json(array('code' => '403.1', 'message' => $message));
        }
    }

    function IsEmpty($content)
    {
        return @(is_null($content)|| empty($content));
    }

    function InsertId($id){
        DB::connection('mysql_alumni')->table('user_auth')->insert(['id' => $id]);
    }

    function EmptyCheck($type,$info,$name,&$message)
    {
        if(self::isEmpty($info)){
            array_push($message, '请填写您所就读的'.$name.'全名。请不要使用任何简写。');
            return true;
        }
        else return false;
    }

    function SchoolYear
    //auth data
    function AuthStep2($info)
    {
        $message = array();
        /*
            JSON格式：
                primary_school：学校id
                primary_school_name：学校全名
                primary_school_enter_year：入学年份
                primary_school_graduated_year：毕业年份
                remark：备注
        */
        if(!@self::SchoolIdEmptyCheck($info->primary_school,"小学",$message))
        {
            switch($info->primary_school)
            {
                case self::OTHER_PRIMARY:
                    if(@self::isEmpty($info->primary_school_name))
                        array_push($message, '请填写您所就读的小学全名。请不要使用任何简写。');
                    break;
                case self::NFLS_PRIMARY:
                    $passed = true;
                    if(@self::isEmpty($info->primary_school_enter_year))
                    {
                        array_push($message, '请填写您小学的入学年份。');
                        $passed = false;
                    }
                    if(@self::isEmpty($info->primary_school_graduated_year))
                    {
                        array_push($message, '请填写您小学的毕业年份。'); 
                        $passed = false;
                    }
                    if($passed)
                    {
                        $valid  = new Between(['min' => 1963, 'max' => 1982]);//enter before 1979
                        if(!$valid->isValid($info->primary_school_enter_year) || !is_integer($info->primary_school_enter_year))
                            array_push($message, '小学入学年份不正确！请检查您的入学年份。此项仅支持1979年之前入学校友填写，并请不要输入非数字内容。');
                        if(!$valid->isValid($info->primary_school_graduated_year) || !is_integer($info->primary_school_graduated_year))
                            array_push($message, '小学毕业年份不正确！请检查您的毕业年份。此项仅支持1979年之前入学校友填写，并请不要输入非数字内容。');
                        if($info->primary_school_enter_year + 4 != $info->primary_school_graduated_year && (@self::isEmpty($info->remark)))
                            array_push($message, '小学毕业年份与入学年份不对应！如果有特殊情况，请在备注中注明。');
                    }
                    break;
                default:
                    array_push($message,'小学信息不正确！请重新选择。');
                    break;
            }
        }
        return $message;
    }

    function AuthStep3($info)
    {
        $message = array();
        /*
            JSON格式：
                junior_school：学校id
                junior_school_name：学校全名
                junior_school_enter_year：入学年份
                junior_school_graduated_year：毕业年份
                junior_class：班级号
                remark：备注
        */
        if(@self::isEmpty($info->junior_school))
            array_push($message,'请选择您所就读的初中。');
        else
        {
            switch($info->junior_school)
            {
                case self::OTHER_JUNIOR:
                    if(@self::isEmpty($info->junior_school_name))
                        array_push($message, '请填写您所就读的初中全名。请不要使用任何简写。');
                    if(count((array)$info)!=3)
                        array_push($message, '您提交的信息存在结构性问题，请重试或解决上面提到的任何错误。如果此错误持续发生，请联系管理员。');
                    break;
                case self::NFLS_JUNIOR:
                    $passed = true;
                    if(@self::isEmpty($info->junior_school_enter_year))
                    {
                        array_push($message, '请填写您初中的入学年份。');
                        $passed = false;
                    }
                    if(@self::isEmpty($info->junior_school_graduated_year))
                    {
                        array_push($message, '请填写您初中的毕业年份。'); 
                        $passed = false;
                    }
                    if(@self::isEmpty($info->junior_class))
                    {
                        array_push($message, '请填写您初中的班级号。'); 
                        $passed = false;
                    }
                    if($passed)
                    {
                        $valid  = new Between(['min' => 1963, 'max' => date('Y') - 6]);
                        if(!$valid->isValid($info->junior_school_enter_year) || !is_int($info->junior_school_enter_year))
                            array_push($message, '初中入学年份不正确！请检查您的入学年份。目前允许的最大年份为'.(String)(date('Y') - 6).'年');
                        unset($valid);
                        $valid  = new Between(['min' => 1963, 'max' => date('Y') - 3]);
                        if(!$valid->isValid($info->junior_school_graduated_year) || !is_integer($info->junior_school_graduated_year))
                            array_push($message, '初中毕业年份不正确！请检查您的毕业年份。目前允许的最大年份为'.(String)(date('Y') - 3).'年');
                        if($info->junior_school_enter_year + 3 != $info->junior_school_graduated_year && (@self::isEmpty($info->remark)))
                            array_push($message, '初中毕业年份与入学年份不对应！如果有特殊情况，请在备注中注明。');
                        unset($valid);
                        $valid = new Between(['min' => 1, 'max' => 12]);
                        if(!$valid->isValid($info->junior_class) || !is_integer($info->junior_class))
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
        return $message;
    }

    function AuthStep4($info)
    {
        $message = array();
        /*
            JSON格式：
                senior_school：学校id
                senior_school_name：学校全名
                senior_school_enter_year：入学年份
                senior_school_graduated_year：毕业年份
                senior_class：班级号（国际部）
                senior_class_11：班级号（高一上）
                senior_class_12：班级号（高一下）
                senior_class_21：班级号（高二上）
                senior_class_22：班级号（高二下）
                senior_class_31：班级号（高三上）
                senior_class_32：班级号（高三下）
                graduate_info: 1[保送] 2[高考] 3[提前高考] 4[出国] 5[提前出国]
                remark：备注
        */
        if(@self::isEmpty($info->senior_school))
            array_push($message,'请选择您所就读的高中。');
        else
        {
            switch($info->senior_school)
            {
                case self::OTHER_SENIOR:
                    if(@self::isEmpty($info->senior_school_name))
                        array_push($message, '请填写您所就读的高中全名。请不要使用任何简写。');
                    if(count((array)$info)!=3)
                        array_push($message, '您提交的信息存在结构性问题，请重试或解决上面提到的任何错误。如果此错误持续发生，请联系管理员。');
                    break;
                case self::NFLS_SENIOR_GENERAL:
                    $passed = true;
                    if(@self::isEmpty($info->senior_school_enter_year))
                    {
                        array_push($message, '请填写您高中的入学年份。');
                        $passed = false;
                    }
                    if(@self::isEmpty($info->senior_school_graduated_year))
                    {
                        array_push($message, '请填写您高中的毕业年份。'); 
                        $passed = false;
                    }
                    if(@self::isEmpty($info->senior_class_11) || @self::isEmpty($info->senior_class_12) || @self::isEmpty($info->senior_class_21) || @self::isEmpty($info->senior_class_22) || @self::isEmpty($info->senior_class_31) || @self::isEmpty($info->senior_class_32))
                    {
                        array_push($message, '请填写您所有高中的班级号。如果该项目对您不适用，请填写0'); 
                        $passed = false;
                    }
                    if($passed)
                    {
                        $valid  = new Between(['min' => 1963, 'max' => date('Y') - 3]);
                        if(!$valid->isValid($info->senior_school_enter_year) || !is_int($info->senior_school_enter_year))
                            array_push($message, '高中入学年份不正确！请检查您的入学年份。目前允许的最大年份为'.(String)(date('Y') - 3).'年');
                        unset($valid);
                        $valid  = new Between(['min' => 1963, 'max' => date('Y') ]);
                        if(!$valid->isValid($info->senior_school_graduated_year) || !is_integer($info->senior_school_graduated_year))
                            array_push($message, '高中毕业年份不正确！请检查您的毕业年份。目前允许的最大年份为'.(String)(date('Y')).'年');
                        if($info->senior_school_enter_year + 3 != $info->senior_school_graduated_year && (@self::isEmpty($info->remark)))
                            array_push($message, '高中毕业年份与入学年份不对应！如果有特殊情况，请在备注中注明。');
                        unset($valid);
                        $valid = new Between(['min' => 0, 'max' => 8]);
                        if(!$valid->isValid($info->senior_class_11) || !is_integer($info->senior_class_11))
                            array_push($message, '高一上班级号不正确！请检查您的高一上班级号是否为1-8之间的任何一个整数。');
                        if(!$valid->isValid($info->senior_class_12) || !is_integer($info->senior_class_12))
                            array_push($message, '高一下班级号不正确！请检查您的高一下班级号是否为1-8之间的任何一个整数。');
                        if(!$valid->isValid($info->senior_class_21) || !is_integer($info->senior_class_21))
                            array_push($message, '高二上班级号不正确！请检查您的高二上班级号是否为1-8之间的任何一个整数。');
                        if(!$valid->isValid($info->senior_class_22) || !is_integer($info->senior_class_22))
                            array_push($message, '高二下班级号不正确！请检查您的高二下班级号是否为1-8之间的任何一个整数。');
                        if(!$valid->isValid($info->senior_class_31) || !is_integer($info->senior_class_31))
                            array_push($message, '高三上班级号不正确！请检查您的高三上班级号是否为1-8之间的任何一个整数。');
                        if(!$valid->isValid($info->senior_class_32) || !is_integer($info->senior_class_32))
                            array_push($message, '高三下班级号不正确！请检查您的高三下班级号是否为1-8之间的任何一个整数。');
                    }
                    if(count((array)$info)!=10)
                        array_push($message, '您提交的信息存在结构性问题，请重试或解决上面提到的任何错误。如果此错误持续发生，请联系管理员。');
                    break;
                case NFLS_SENIOR_AUSTRALIA:
                    $passed = true;
                    if(@self::isEmpty($info->senior_school_enter_year))
                    {
                        array_push($message, '请填写您高中的入学年份。');
                        $passed = false;
                    }
                    if(@self::isEmpty($info->senior_school_graduated_year))
                    {
                        array_push($message, '请填写您高中的毕业年份。'); 
                        $passed = false;
                    }
                    if($passed)
                    {
                        $valid  = new Between(['min' => 1963, 'max' => 2010]);
                        if(!$valid->isValid($info->senior_school_enter_year) || !is_int($info->senior_school_enter_year))
                            array_push($message, '高中入学年份不正确！请检查您的入学年份。目前允许的最大年份为2010年');
                        unset($valid);
                        $valid  = new Between(['min' => 1963, 'max' => 2013]);
                        if(!$valid->isValid($info->senior_school_graduated_year) || !is_int($info->senior_school_graduated_year))
                            array_push($message, '高中入学年份不正确！请检查您的入学年份。目前允许的最大年份为2013年');
                        unset($valid);
                        if($info->senior_school_enter_year + 3 != $info->senior_school_graduated_year && (@self::isEmpty($info->remark)))
                            array_push($message, '高中毕业年份与入学年份不对应！如果有特殊情况，请在备注中注明。');
                    }
                    if(count((array)$info)!=4)
                        array_push($message, '您提交的信息存在结构性问题，请重试或解决上面提到的任何错误。如果此错误持续发生，请联系管理员。');
                    break;
                case NFLS_SENIOR_ALEVEL:
                case NFLS_SENIOR_IB:
                    $passed = true;
                    if(@self::isEmpty($info->junior_school_enter_year))
                    {
                        array_push($message, '请填写您高中的入学年份。');
                        $passed = false;
                    }
                    if(@self::isEmpty($info->junior_school_graduated_year))
                    {
                        array_push($message, '请填写您高中的毕业年份。'); 
                        $passed = false;
                    }
                    if(@self::isEmpty($info->junior_class))
                    {
                        array_push($message, '请填写您高中的班级号。'); 
                        $passed = false;
                    }
                    if($passed)
                    {
                        $valid  = new Between(['min' => 1963, 'max' => date('Y') - 3]);
                        if(!$valid->isValid($info->junior_school_enter_year) || !is_int($info->junior_school_enter_year))
                            array_push($message, '高中入学年份不正确！请检查您的入学年份。目前允许的最大年份为'.(String)(date('Y') - 3).'年');
                        unset($valid);
                        $valid  = new Between(['min' => 1963, 'max' => date('Y') - 3]);
                        if(!$valid->isValid($info->junior_school_graduated_year) || !is_integer($info->junior_school_graduated_year))
                            array_push($message, '高中毕业年份不正确！请检查您的毕业年份。目前允许的最大年份为'.(String)(date('Y') - 3).'年');
                        if($info->junior_school_enter_year + 3 != $info->junior_school_graduated_year && (@self::isEmpty($info->remark)))
                            array_push($message, '高中毕业年份与入学年份不对应！如果有特殊情况，请在备注中注明。');
                        unset($valid);
                        $valid = new Between(['min' => 1, 'max' => 4]);
                        if(!$valid->isValid($info->junior_class) || !is_integer($info->junior_class))
                            array_push($message, '高中班级号不正确！请检查您的高中班级号是否为1-4之间的任何一个整数。');
                    }
                    if(count((array)$info)!=5)
                        array_push($message, '您提交的信息存在结构性问题，请重试或解决上面提到的任何错误。如果此错误持续发生，请联系管理员。');
                    break;
                case NFLS_SENIOR_BCA:
                    
                
                default:
                    array_push($message,'高中信息不正确！请重新选择。');
                    break;
            }
        }
        return $message;
    }
}

