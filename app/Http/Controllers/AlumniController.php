<?php

namespace App\Http\Controllers;

use App\Http\Controllers\UserCenterController;
use Illuminate\Http\Request;
use Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Routing\ResponseFactory;
use League\Flysystem\Exception;
use libphonenumber\NumberParseException;
use Zend\Validator\Between;
use Zend\Validator\Date;
use Response;

class AlumniController extends Controller
{
    const NOT_START = 0;
    const IN_PROGRESS = 1;
    const FINISHED = 2;

    const NFLS_PRIMARY_4 = 1;
    const NFLS_PRIMARY_2 = 1;
    const OTHER_PRIMARY = -1;

    const NFLS_JUNIOR = 1;
    const OTHER_JUNIOR = -1;

    const NFLS_SENIOR_GENERAL = 1;
    const NFLS_SENIOR_IB = 2;
    const NFLS_SENIOR_ALEVEL = 3;
    const NFLS_SENIOR_BCA = 4;
    const NFLS_SENIOR_AUSTRALIA = 5;
    const OTHER_SENIOR = -1;
    const NO_SENIOR = -2;

    const BASIC_INFO = 1;
    const PRIMARY_INFO = 2;
    const JUNIOR_INFO = 3;
    const SENIOR_INFO = 4;

    const SCHOOL_NO = 1;
    const ENTER_YAER = 2;
    const GRADUATED_YEAR = 3;
    const SCHOOL_NAME = 4;
    const CLASS_NO = 5;
    const OTHER = -1;
    //const ERR_STRUCTURE =  TO-DO: Add language

    const SCHOOL_START_YEAR = 1963;
    const PRIMARY_END_YEAR = 1979;
    const IB_START_YEAR = 2011;
    const ALEVEL_4_START_YEAR = 2006;
    const ALEVEL_2_START_YEAR = 2011;
    const BCA_START_YEAR = 2002;
    const AUSTRALIA_START_YEAR = 2007;
    const AUSTRALIA_END_YEAR = 2012;

    const GENDER_NOT_CHOOSE = 0;
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;
    const GENDER_OTHER = 3;

    const STEP1 = [
        '填写此表格前请确认您的用户名及邮箱是否正确',
        '生日请使用yyyy/mm/dd的格式填写，即如果你的生日在2000年1月1日，就请填写2000/01/01，注意符号为半角符号',
        '昵称或英文名请填写在南外期间常用的，如英语课上的英文名，或者是同学之间的昵称，如有多个请用半角逗号","分割；如果更改过姓名请填写曾用名',
        '手机号请务必填写正确，在未来可能会启用手机号验证系统',
        '出国的同学请填写自己的国外手机号，并请加上正确的国际区号，以便联系',
        '本页除"曾用名"项外均为必填项目，"手机号码（国外）"仅需要长期不在国内的校友（如读书或工作等）填写'
    ];
    function getCurrentStep(Request $request)
    {
        $id = self::getUser(Cookie::get('token'));
        $user = DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->first();

        switch ($user->current_step){
            case 1:
                $instructions = self::STEP1;
                break;
            default:
                $instructions = [];
                break;
        }
        return Response::json(array('code' => '200', 'instructions'=>$instructions, 'step' => $user->current_step));
    }

    function getUser($token)
    {
        $id = UserCenterController::GetUserId($token);
        if ($id < 0) {
            abort(403);
            return false;
        }
        $user = DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->first();
        if (is_null($user))
            self::InsertId($id);
        return $id;
    }

    function AuthUpdate(Request $request, $step)
    {
        if (is_numeric($step) == true) {
            $id = UserCenterController::GetUserId(Cookie::get('token'));
            if ($id < 0) {
                return Response::json(array("code" => "23333"));
            }
            $user = DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->first();
            if (is_null($user))
                self::InsertId($id);
            $content = file_get_contents('php://input');
            $info = json_decode($content);
            if (!is_object($info))
                abort("404", "Check your input!");
            switch ($step) {
                case self::BASIC_INFO:
                    $message = self::AuthStep1($info);
                    return self::DataCheck($message, $id, $info, 'auth_info');
                case self::PRIMARY_INFO:
                    $message = self::AuthStep2($info);
                    return self::DataCheck($message, $id, $info, 'primary_info');
                    break;
                case self::JUNIOR_INFO:
                    $message = self::AuthStep3($info);
                    return self::DataCheck($message, $id, $info, 'junior_info');
                    break;
                case self::SENIOR_INFO:
                    $message = self::AuthStep4($info);
                    return self::DataCheck($message, $id, $info, 'senior_info');
                    break;
            }
        }
    }

    function AuthQuery(Request $request, $step)
    {
        $return_array = array();
        $return_array['id'] = self::getUser(Cookie::get('token'));
        $return_array['code'] = 200;
        $return_array['message'] = "一切正常";
        switch ($step) {
            case 1:
                $return_array['info']['email'] = UserCenterController::GetUserEmail($return_array['id']);
                $return_array['info']['username'] = UserCenterController::GetUserNickname($return_array['id']);
                $info = DB::connection('mysql_alumni')->table('user_auth')->where('id', $return_array['id'])->first()->auth_info;
                if(!is_null($info))
                    $return_array['info'] = array_merge(json_decode(DB::connection('mysql_alumni')->table('user_auth')->where('id', $return_array['id'])->first()->auth_info,true),$return_array['info']);
                return Response::json($return_array);
            default:
                break;
        }
    }

    function DataCheck($message, $id, $content, $name, $insert = true)
    {
        if (empty($message)) {
            array_push($message, '恭喜您，所有当前的数据均符合要求！');
            if ($insert)
                DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->update([$name => json_encode($content)]);
            return Response::json(array('code' => '200', 'message' => $message));
        } else {
            array_unshift($message, '非常抱歉，您提交的数据在以下部分存在问题：');
            return Response::json(array('code' => '403.1', 'message' => $message));
        }
    }

    function IsEmpty($content)
    {
        return @(is_null($content) || empty($content));
    }

    function InsertId($id)
    {
        DB::connection('mysql_alumni')->table('user_auth')->insert(['id' => $id]);
    }


    function EmptyCheck($type, $info, $name, &$message, $addMessage = true)
    {
        switch ($type) {
            case self::SCHOOL_NO:
                if (self::isEmpty($info)) {
                    if ($addMessage)
                        array_push($message, '请选择您所就读的' . $name . '。');
                    return false;
                }
                break;
            case self::ENTER_YAER:
                if (self::isEmpty($info)) {
                    if ($addMessage)
                        array_push($message, '请填写您' . $name . '的入学年份。');
                    return false;
                }
                break;
            case self::GRADUATED_YEAR:
                if (self::isEmpty($info)) {
                    if ($addMessage)
                        array_push($message, '请填写您' . $name . '的毕业年份。');
                    return false;
                }
                break;
            case self::SCHOOL_NAME:
                if (self::isEmpty($info)) {
                    if ($addMessage)
                        array_push($message, '请填写您所就读的' . $name . '全名。请不要使用任何简写。');
                    return false;
                }
                break;
            case self::CLASS_NO:
                if (self::isEmpty($info)) {
                    if ($addMessage)
                        array_push($message, '请填写您的' . $name . '初中班级号。');
                    return false;
                }
                break;
            default:
                if (self::isEmpty($info)) {
                    if ($addMessage)
                        array_push($message, '请填写您的' . $name . '。');
                    return false;
                }
        }
        return true;
    }

    function ClassNoCheck($info, $min, $max, $name, &$message)
    {
        $valid = new Between(['min' => $min, 'max' => $max]);
        if (!$valid->isValid($info) || !is_integer($info))
            array_push($message, $name . '班级号不正确。');

    }


    function SchoolYearCheck($enter_year, $graduated_year, $minimum_year, $maximum_year, $interval, $remark, $name, &$message)
    {
        $valid = new Between(['min' => $minimum_year, 'max' => $maximum_year]);
        if (!$valid->isValid($enter_year) || !is_integer($enter_year))
            array_push($message, $name . '入学年份不正确。');
        unset($valid);
        $valid = new Between(['min' => $minimum_year + $interval, 'max' => $maximum_year + $interval]);
        if (!$valid->isValid($graduated_year) || !is_integer($graduated_year))
            array_push($message, $name . '毕业年份不正确。');
        if ($enter_year + $interval != $graduated_year && (@self::isEmpty($remark)))
            array_push($message, $name . '毕业年份与入学年份不对应！如果有特殊情况，请在备注中注明。');
    }

    function StructureCheck($info, $count, &$message)
    {
        if (count((array)$info) != $count)
            array_push($message, '您提交的信息存在结构性问题，请重试或解决上面提到的任何错误。如果此错误持续发生，请联系管理员。');
    }

    //auth data

    function AuthStep1($info)
    {
        /*
            JSON格式：
                username：用户名
                email：邮箱
                usedname：曾用名
                realname：真实姓名
                phone_domestic：中国手机号
                phone_international：外国手机号（含区号）［二选一］
                nickname：英文名或绰号
                birthday：出生日期
                gender：性别
abandoned
                ［选填内容，至少填一个］
                wechat：微信号
                qq：QQ号
                telegram：telegram账户
                whatsapp：whatsapp账户
        ...TO-DO：ADD MORE

        */
        $message = array();
        @self::EmptyCheck(self::OTHER, $info->realname, '真实姓名', $message);
        if(mb_strlen($info->realname) >=2 || mb_strlen($info->realname) <=4) {
            $len = mb_strlen($info->realname);
            $width = mb_strwidth($info->realname);
            if($len*2 != $width)
                array_push($message,'真实姓名内容不符合要求。请输入2-4个中文字符');
        }
        else
        {
            array_push($message,'真实姓名内容不符合要求。请输入2-4个中文字符');
        }
        if(@self::EmptyCheck(self::OTHER, $info->phone_domestic, '手机号码（国内）', $message)){
            if(!preg_match("/^1[34578]\d{9}$/", (int)($info->phone_domestic))){
                array_push($message, '国内手机号码不正确！');
            }
        }
        if(@self::EmptyCheck(self::OTHER, $info->phone_international, '手机号码（国外）', $message, false)){
            try {
                $phone_validate = \libphonenumber\PhoneNumberUtil::getInstance();
                $phone_number = $phone_validate->parse($info->phone_international, \libphonenumber\PhoneNumberFormat::INTERNATIONAL);
                if($phone_validate->isValidNumber($phone_number)){
                    array_push($message, '国外手机号码不正确！请检查国际区号或者手机号码是否正确。');
                }
            } catch (NumberParseException $e) {
                array_push($message, '国外手机号码不正确！请检查国际区号或者手机号码是否正确。');
            }
        }
        if(@self::EmptyCheck(self::OTHER, $info->nickname, '昵称或英文名', $message)){
            $names = explode(',', $info->nickname);
            if(count($names) <= 0 ){
                array_push($message, "昵称或英文名分割错误，请检查您的输入内容。");
            }
        }
        if(@self::EmptyCheck(self::OTHER, $info->birthday, '出生日期', $message)){
            $validator = new Date(['format' => 'Y/m/d']);
            if(!$validator->isValid($info->birthday)){
                array_push($message,'出生日期格式不符合要求。');
            }
            if(date('Y',strtotime($info->birthday)) + 18 > date('Y')){
                array_push($message,'请在高中毕业后填写此表格。');
            }
            if(date('Y',strtotime($info->birthday)) + 18 < 1963){
                array_push($message,'您的年龄不符合最低入学要求。');
            }
        }
        if(@self::EmptyCheck(self::OTHER, $info->gender, '性别', $message)){
            $info->gender = (int)($info->gender);
            $valid = new Between(['min' => self::GENDER_MALE, 'max' => self::GENDER_OTHER]);
            if (!$valid->isValid($info->gender))
                array_push($message , '性别不正确。');
        }
        if(@self::EmptyCheck(self::OTHER, $info->usedname, '曾用名', $message, false)){
            if(mb_strlen($info->usedname) >=2 || mb_strlen($info->usedname) <=4) {
                $len = mb_strlen($info->usedname);
                $width = mb_strwidth($info->usedname);
                if($len*2 != $width)
                    array_push($message,'曾用名内容不符合要求。请输入2-4个中文字符');
            }
            else
            {
                array_push($message,'曾用名内容不符合要求。请输入2-4个中文字符');
            }
        }
        return $message;
    }

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
        if (@self::EmptyCheck(self::SCHOOL_NO, $info->primary_school, "小学", $message)) {
            //两年毕业 ！！！
            switch ($info->primary_school) {
                case self::OTHER_PRIMARY:
                    @self::EmptyCheck(self::SCHOOL_NAME, $info->primary_school_name, "小学", $message);
                    self::StructureCheck($info, 2, $message);
                    break;
                case self::NFLS_PRIMARY_2:
                    $passed = @self::EmptyCheck(self::ENTER_YAER, $info->primary_school_enter_year, "小学", $message);
                    $passed = @self::EmptyCheck(self::GRADUATED_YEAR, $info->primary_school_graduated_year, "小学", $message);
                    if ($passed)
                        @self::SchoolYearCheck($info->primary_school_enter_year, $info->primary_school_graduated_year, self::SCHOOL_START_YEAR, self::PRIMARY_END_YEAR, 4, $info->remark, "小学", $message);
                    self::StructureCheck($info, 2, $message);
                    break;
                case self::NFLS_PRIMARY_4:
                    $passed = @self::EmptyCheck(self::ENTER_YAER, $info->primary_school_enter_year, "小学", $message);
                    $passed = @self::EmptyCheck(self::GRADUATED_YEAR, $info->primary_school_graduated_year, "小学", $message);
                    if ($passed)
                        @self::SchoolYearCheck($info->primary_school_enter_year, $info->primary_school_graduated_year, self::SCHOOL_START_YEAR, self::PRIMARY_END_YEAR, 4, $info->remark, "小学", $message);
                    self::StructureCheck($info, 4, $message);
                    break;
                default:
                    array_push($message, '小学信息不正确！请重新选择。');
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
        if (@self::EmptyCheck(self::SCHOOL_NO, $info->junior_school, "初中", $message)) {
            switch ($info->junior_school) {
                case self::OTHER_JUNIOR:
                    @self::EmptyCheck(self::SCHOOL_NAME, $info->junior_school_name, "初中", $message);
                    self::StructureCheck($info, 3, $message);
                case self::NFLS_JUNIOR:
                    $passed = @self::EmptyCheck(self::SCHOOL_START_YEAR, $info->junior_school_enter_year, "初中", $message);
                    $passed = @self::EmptyCheck(self::SCHOOL_START_YEAR, $info->junior_school_graduated_year, "初中", $message);
                    $passed = @self::EmptyCheck(self::CLASS_NO, $info->junior_class, "初中", $message);
                    if ($passed) {
                        @self::SchoolYearCheck($info->junior_school_enter_year, $info->junior_school_graduated_year, self::SCHOOL_START_YEAR, date('Y') - 6, 3, $info->remark, "初中", $message);
                        @self::ClassNoCheck($info->junior_class, 1, 12, "初中", $message);
                    }
                    self::StructureCheck($info, 5, $message);
                default:
                    array_push($message, '初中信息不正确！请重新选择。');
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
                graduate_info: 1[保送] 2[高考] 3[提前高考] 4[出国] 5[提前出国] 6[其他]
                remark：备注
        */
        if (@self::isEmpty($info->senior_school))
            array_push($message, '请选择您所就读的高中。');
        else {
            switch ($info->senior_school) {
                case self::OTHER_SENIOR:
                    @self::EmptyCheck(self::SCHOOL_NAME, $info->senior_school_name, "高中", $message);
                    self::StructureCheck($info, 3, $message);
                    break;
                case self::NFLS_SENIOR_GENERAL:
                    $passed = self::EmptyCheck(self::ENTER_YAER, $info->senior_school_enter_year, "高中", $message);
                    $passed = self::EmptyCheck(self::GRADUATED_YEAR, $info->senior_school_graduated_year, "高中", $message);
                    $passed = self::EmptyCheck(self::CLASS_NO, $info->senior_class_11, "高一上", $message);
                    $passed = self::EmptyCheck(self::CLASS_NO, $info->senior_class_12, "高一下", $message);
                    $passed = self::EmptyCheck(self::CLASS_NO, $info->senior_class_21, "高二上", $message);
                    $passed = self::EmptyCheck(self::CLASS_NO, $info->senior_class_22, "高二下", $message);
                    $passed = self::EmptyCheck(self::CLASS_NO, $info->senior_class_31, "高三上", $message);
                    $passed = self::EmptyCheck(self::CLASS_NO, $info->senior_class_32, "高三下", $message);
                    if ($passed) {
                        @self::SchoolYearCheck($info->senior_school_enter_year, $info->senior_school_graduated_year, self::SCHOOL_START_YEAR, date('Y') - 3, 3, $info->remark, "高中", $message);//
                        @self::ClassNoCheck($info->senior_class_11, 1, 8, "高一上", $message);
                        @self::ClassNoCheck($info->senior_class_12, 0, 8, "高一下", $message);
                        @self::ClassNoCheck($info->senior_class_21, 0, 8, "高二上", $message);
                        @self::ClassNoCheck($info->senior_class_22, 0, 8, "高二下", $message);
                        @self::ClassNoCheck($info->senior_class_31, 0, 8, "高三上", $message);
                        @self::ClassNoCheck($info->senior_class_32, 0, 8, "高三下", $message);
                    }
                    self::StructureCheck($info, 10, $message);
                    break;
                /*
                case NFLS_SENIOR_AUSTRALIA:
                    $passed = self::EmptyCheck(self::ENTER_YAER,$info->senior_school_enter_year,"高中",$message);
                    $passed = self::EmptyCheck(self::GRADUATED_YEAR,$info->senior_school_graduated_year,"高中",$message);
                    if($passed)
                        @self::SchoolYearCheck($info->senior_school_enter_year,$info->senior_school_graduated_year,self::SCHOOL_START_YEAR,date('Y') - 3,3,$info->remark,"高中",$message);
                    self::StructureCheck($info,4,$message);
                    break;
                */
                case NFLS_SENIOR_ALEVEL:
                    $passed = self::EmptyCheck(self::ENTER_YAER, $info->senior_school_enter_year, "高中", $message);
                    $passed = self::EmptyCheck(self::GRADUATED_YEAR, $info->senior_school_graduated_year, "高中", $message);
                    if ($passed) {
                        if ($info->senior_school_enter_year > self::ALEVEL_2_START_YEAR) {
                            @self::SchoolYearCheck($info->senior_school_enter_year, $info->senior_school_graduated_year, self::ALEVEL_2_START_YEAR, date('Y') - 3, 3, $info->remark, "高中", $message);
                            @self::ClassNoCheck($info->junior_class, 1, 2, "高中", $message);
                        } else {
                            @self::SchoolYearCheck($info->senior_school_enter_year, $info->senior_school_graduated_year, self::ALEVEL_4_START_YEAR, date('Y') - 3, 3, $info->remark, "高中", $message);
                            @self::ClassNoCheck($info->junior_class, 1, 4, "高中", $message);
                        }
                    }
                    self::StructureCheck($info, 5, $message);
                    break;
                case NFLS_SENIOR_IB:
                    $passed = self::EmptyCheck(self::ENTER_YAER, $info->senior_school_enter_year, "高中", $message);
                    $passed = self::EmptyCheck(self::GRADUATED_YEAR, $info->senior_school_graduated_year, "高中", $message);
                    if ($passed) {
                        @self::SchoolYearCheck($info->senior_school_enter_year, $info->senior_school_graduated_year, self::IB_START_YEAR, date('Y') - 3, 3, $info->remark, "高中", $message);
                        @self::ClassNoCheck($info->junior_class, 1, 2, "高中", $message);
                    }
                    self::StructureCheck($info, 5, $message);
                    break;
                case NFLS_SENIOR_BCA:
                    $passed = self::EmptyCheck(self::ENTER_YAER, $info->senior_school_enter_year, "高中", $message);
                    $passed = self::EmptyCheck(self::GRADUATED_YEAR, $info->senior_school_graduated_year, "高中", $message);
                    if ($passed) {
                        @self::SchoolYearCheck($info->senior_school_enter_year, $info->senior_school_graduated_year, self::BCA_START_YEAR, date('Y') - 3, 3, $info->remark, "高中", $message);
                        @self::ClassNoCheck($info->junior_class, 1, 6, "高中", $message);
                    }
                    self::StructureCheck($info, 5, $message);
                    break;
                default:
                    array_push($message, '高中信息不正确！请重新选择。');
                    break;
            }
        }
        return $message;
    }
}

