<?php

namespace App\Http\Controllers;

use App\Http\Controllers\UserCenterController;
use Illuminate\Http\Request;
use Cookie;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Routing\ResponseFactory;
use League\Flysystem\Exception;
use libphonenumber\NumberParseException;
use Zend\Validator\Between;
use Zend\Validator\Date;
use Response;

class CertificationController extends Controller
{
    const NOT_START = 0;
    const IN_PROGRESS = 1;
    const FINISHED = 2;

    const NFLS_PRIMARY_4 = 1;
    const NFLS_PRIMARY_2 = 2;
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
    const CHECK_INFO = 5;
    const COLLEGE_INFO = 6;
    const WORK_INFO = 7;
    const PERSONAL_INFO = 8;

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


    const GENERAL_INSTRUCTION = [
        '本认证共八步，前五步将确认您的校友信息，提交并审核通过后无法修改（手机号除外），后三步为后续经历填写，可随时修改',
        '审核时间约为1-5天，审核期间您可以完善后续表格，您只有在完成所有表格后才能使用在线校友录功能',
        '对于您提供的信息，您可在认证结束后自行修改相关隐私设置（默认设置为：同一届同学可见）',
        '所有入学/毕业日期只记录年份',
        '所有学校名称请使用完整的官方名称，不要使用任何简写、简拼等',
    ];

    const STEP1 = [
        '填写此表格前请确认您的用户名及邮箱是否正确',
        '昵称或英文名请填写在南外期间常用的，如英语课上的英文名，或者是同学之间的昵称，如有多个请用半角逗号分隔；如果更改过姓名请填写曾用名',
        '手机号请务必填写正确，在未来可能会启用手机号验证系统',
        '出国的同学请填写自己的国外手机号，并请加上正确的国际区号，以便联系',
        '本页除“曾用名”项外均为必填项目，“手机号码（国外）”仅需要当前不在国内的校友填写',
        '本表格在校学生仅限国际部高一及以上，普高高二及以上的同学填写。',
        '普高高二在读请在高三相关的班级号上填入与高二相同的班级号，如有发生变更，可后期申诉修改。'
    ];

    const STEP2 = [
        '请在本页填写您的小学信息',
        '如果您就读过南外小学部，请在“小学就读学校”处填写就读的小学；毕业和入学日期均是指离开或是进入南外小学部的年份。',
        '如果存在其他特殊情况，请在备注中详细注明具体情况'
    ];

    const STEP3 = [
        '请在本页填写您的初中信息',
        '病假／休学等特殊情况导致在校时间超过3年或者存在多个班级号的情况，请在备注中详细注明相关情况（包括两个班级的班级号，发生改动的学期等等）',
        '班级号请填写纯数字1-12，请不要填写英语小班分班号',
    ];

    const STEP4 = [
        '请在本页填写您的高中信息',
        '普高请填写6个学期的班级号，一位阿拉伯数字。如果存在不适用的情况（如提前高考或者是提前出国），请在对应班级号处填写0，请不要填写英语小班分班号',
        '病假／休学等特殊情况导致在校时间超过3年或者存在多个班级号的情况，请在备注中详细注明相关情况（请假开始的学期等等）',
        'IB/ALEVEL请填写一位阿拉伯数字班级号，中加请填写一个英文大写字母的班级号',
    ];

    const STEP5 = [
        '请您确认您在前四步填写的信息准确、无误',
        '如果您的表单存在错误，将在下面的区域内显示，请修正后在提交',
        '单击下一步，您可以提交您的校友认证，您的信息将由管理员进行审核',
        '审核期间，您可以继续填写下面的表格，请注意，您只有在完成所有表格后才能使用在线校友录功能'
    ];
	
	const STEP6 = [
        '请在本页填写您的大学信息',
        '如果您已经获得或正在获得相关学历，请在对应方框内打钩，并填写相关内容。若还没有毕业可填写预估的毕业年份',
		'如果您有除了列表内容以外的学历，请在“其他”中填写',
		'请至少填写一个内容'
    ];
	
	const STEP7 = [
        '请在本页填写您的工作信息',
        '本区域可自由发挥，介绍工作的公司，职业类型什么的都可以。文本框会根据内容自动调整大小。',
		'如果您暂时还在就读大学，可留空。'
    ];
	
	const STEP8 = [
        '请在本页填写您的个人信息',
        '本区域可自由发挥，可填写自我介绍等各类关于自己的内容。文本框会根据内容自动调整大小。',
		'底下的联系方式，可根据自己的情况选填，但至少必须填写一个。'
    ];

	const STEP9 = [
	    '您已完成所有内容。待审核通过后，您即可使用在线校友录功能。您也可以返回编辑。前四步已经提交，除审核被退回外不可编辑。'
    ];



    function getCurrentStep(Request $request)
    {
        $id = $this->getUser(Cookie::get('token'));
        $user = DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->first();
        switch ($user->current_step) {
            case 1:
                $instructions = self::STEP1;
                break;
            case 2:
                $instructions = self::STEP2;
                break;
            case 3:
                $instructions = self::STEP3;
                break;
            case 4:
                $instructions = self::STEP4;
                break;
            case 5:
                $instructions = self::STEP5;
                break;
            case 6:
                $instructions = self::STEP6;
                break;
            case 7:
                $instructions = self::STEP7;
                break;
            case 8:
                $instructions = self::STEP8;
                break;
            case 9:
                $instructions = self::STEP9;
                break;
            default:
                $instructions = [];
                break;
        }
        return Response::json(array('code' => 200, 'messgae'=> '一切正常','instructions' => $instructions, 'step' => $user->current_step));
    }


    function getCurrentStatus(Request $request)
    {
        $id = $this->getUser(Cookie::get('token'));
        if(Cookie::get("admin") == "true" && !is_null(Cookie::get("current_id"))){
            if(UserCenterController::checkAdmin(UserCenterController::GetUserId(Cookie::get("token")))){
                return Response::json(array('code' => 200, 'message' => array("您已进入管理员模式","当前修改用户：" . (Cookie::get("current_id")) . "|".  UserCenterController::GetUserEmail(Cookie::get("current_id")))));
            } else {
                abort(403);
            }
        }

        $return = array();
        $user = DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->first();
        if ($user->status == false)
            array_push($return, '状态：未实名认证');
        else
            array_push($return, '状态：已实名认证');
        if($user->status == false && !$this->isEmpty($user->submit_time) && !$this->isEmpty($user->status_change_time) && strtotime($user->status_change_time) > strtotime($user->submit_time))
            array_push($return,'审核状态：未通过');
        else if($user->status == false && !$this->isEmpty($user->submit_time) && $this->isEmpty($user->status_change_time))
            array_push($return,'审核状态：等待审核');
        if (!is_null($user->status_change_time))
            array_push($return, '审核时间：' . $user->status_change_time);
        if (!is_null($user->operator))
            array_push($return, '审核员：' . $user->operator);
        if (!is_null($user->edit_time))
            array_push($return, '最近一次编辑时间：' . $user->edit_time);
        if (is_null($user->submit_time)){
            array_push($return, '是否提交：未提交');
            array_push($return, '编辑权限：1-5步');
        }
        else {
            array_push($return, '是否提交：已提交');
            array_push($return, '提交时间为：' . $user->submit_time);
            array_push($return, '编辑权限：6-8步');
        }
        return Response::json(array('code' => 200, 'message' => $return));

    }

    function getDuration(Request $request){

        $id = $this->getUser(Cookie::get('token'));
        $user = DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->first();
        $birth_year = mb_substr(json_decode($user->auth_info,true)['birthday'],0,4);
        $birth_month = mb_substr(json_decode($user->auth_info,true)['birthday'],5,2);
        $birth_day = mb_substr(json_decode($user->auth_info,true)['birthday'],8,2);
        $now = date('Y');
        return Response::json(array("max"=>$now+5,"min_year"=>(int)($birth_year),"min_month"=>(int)($birth_month),"min_day"=>(int)($birth_day)));
    }

    function getUser($token)
    {
        if(Cookie::get("admin") == "true" && !is_null(Cookie::get("current_id"))){
            if(UserCenterController::checkAdmin(UserCenterController::GetUserId(Cookie::get("token")))){
                if(UserCenterController::isUserExist(Cookie::get("current_id"))){
                    $id = Cookie::get("current_id");
                } else {
                    abort(403);
                }
            } else {
                abort(403);
            }
        } else {
            $id = UserCenterController::GetUserId($token);
            if ($id < 0) {
                abort(403);
                return false;
            }
        }

        $user = DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->first();
        if (is_null($user))
            self::InsertId($id);
        return $id;
    }

    function getInstructions()
    {
        return Response::json(array('message' => self::GENERAL_INSTRUCTION));
    }

    function authUpdate(Request $request, $step)
    {
        if (is_numeric($step) == true) {
            $id = $this->getUser(Cookie::get('token'));
            if ($id < 0) {
                return Response::json(array('code' => '23333'));
            }
            $user = DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->first();
            if((int)$user->current_step != $step)
                return Response::json(array('code' => 403, 'message' => array('数据不匹配！请点击重置或刷新网页再试。')));
            if (is_null($user))
                self::InsertId($id);
            $content = file_get_contents('php://input');
            $info = json_decode($content);
            $action = $info->action;
            unset( $info->action);
            if (!is_object($info))
                abort('404', 'Check your input!');
            switch ($step) {
                case self::BASIC_INFO:
                    $message = $this->authStep1($info);
                    return $this->dataCheck($message, $id, $info, self::BASIC_INFO, 'auth_info',$action);
                case self::PRIMARY_INFO:
                    $message = $this->authStep2($info);
                    return $this->dataCheck($message, $id, $info, self::PRIMARY_INFO, 'primary_school',$action);
                    break;
                case self::JUNIOR_INFO:
                    $message = $this->authStep3($info);
                    return $this->dataCheck($message, $id, $info, self::JUNIOR_INFO, 'junior_school',$action);
                    break;
                case self::SENIOR_INFO:
                    $message = $this->authStep4($info);
                    return $this->dataCheck($message, $id, $info, self::SENIOR_INFO, 'senior_school',$action);
                    break;
                case self::CHECK_INFO:
                    $message = $this->authStep5($id);
                    if((int)$action == 1)
                    {
                        if (empty($message)) {
                            array_push($message, '您提交的数据已保存至数据库，即将进入下一步。');
                            DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->update(['current_step' => $step + 1, 'submit_time' => date('y-m-d h:i:s')]);
                            return Response::json(array('code' => 200, 'message' => $message));
                        } else {
                            array_unshift($message, '非常抱歉，您提交的数据在以下部分存在问题：');
                            return Response::json(array('code' => '403.1', 'message' => $message));
                        }
                    } else if ((int)$action == -1){
                        DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->update(['current_step' => (int)$user->current_step - 1]);
                        return Response::json(array('code' => 200, 'message' => array('您即将返回至上一步')));
                    }
                    break;
                case self::COLLEGE_INFO:
                    $message = $this->authStep6($info);
                    return $this->dataCheck($message, $id, $info, self::COLLEGE_INFO, 'college',$action);
                    break;
                case self::WORK_INFO:
                    $message = $this->authStep7($info);
                    return $this->dataCheck($message, $id, $info, self::WORK_INFO, 'working_info',$action);
                    break;
                case self::PERSONAL_INFO:
                    $message = $this->authStep8($info);
                    return $this->dataCheck($message, $id, $info, self::PERSONAL_INFO, 'personal_info',$action);
                    break;
                case 9:
                    DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->update(['current_step' => (int)$user->current_step - 1]);
                    return Response::json(array('code' => 200, 'message' => array('您即将返回至上一步')));
            }
        }
    }

    function authQuery(Request $request, $step)
    {
        $return_array = array();
        $return_array['id'] = $this->getUser(Cookie::get('token'));
        $return_array['code'] = 200;
        $return_array['message'] = '一切正常';
        switch ($step) {
            case self::BASIC_INFO:
                $return_array['info']['email'] = UserCenterController::GetUserEmail($return_array['id']);
                $return_array['info']['username'] = UserCenterController::GetUserNickname($return_array['id']);
                $info = DB::connection('mysql_alumni')->table('user_auth')->where('id', $return_array['id'])->first()->auth_info;
                if (!is_null($info))
                    $return_array['info'] = array_merge(json_decode($info, true), $return_array['info']);
                return Response::json($return_array);
            case self::PRIMARY_INFO:
                $info = DB::connection('mysql_alumni')->table('user_auth')->where('id', $return_array['id'])->first()->primary_school;
                if (!is_null($info))
                    $return_array['info'] = json_decode($info, true);
                return Response::json($return_array);
            case self::JUNIOR_INFO:
                $info = DB::connection('mysql_alumni')->table('user_auth')->where('id', $return_array['id'])->first()->junior_school;
                if (!is_null($info))
                    $return_array['info'] = json_decode($info, true);
                return Response::json($return_array);
            case self::SENIOR_INFO:
                $info = DB::connection('mysql_alumni')->table('user_auth')->where('id', $return_array['id'])->first()->senior_school;
                if (!is_null($info))
                    $return_array['info'] = json_decode($info, true);
                return Response::json($return_array);
            case self::CHECK_INFO:
                $return_array['info']['confirm_info'] = $this->authStep5($return_array['id']);
                if(count($return_array['info'])==0)
                    array_push($return_array['info'],"如果确认输入无误的话，请点击下一步进行提交。");
                else
                    array_unshift($return_array['info'],"您的表格存在以下错误，请再次检查填写内容是否正确！");
                return Response::json($return_array);
            case self::COLLEGE_INFO:
                $info = DB::connection('mysql_alumni')->table('user_auth')->where('id', $return_array['id'])->first()->college;
                if (!is_null($info))
                    $return_array['info'] = json_decode($info, true);
                return Response::json($return_array);
            case self::WORK_INFO:
                $info = DB::connection('mysql_alumni')->table('user_auth')->where('id', $return_array['id'])->first()->working_info;
                if (!is_null($info))
                    $return_array['info'] = json_decode($info, true);
                return Response::json($return_array);
            case self::PERSONAL_INFO:
                $info = DB::connection('mysql_alumni')->table('user_auth')->where('id', $return_array['id'])->first()->personal_info;
                if (!is_null($info))
                    $return_array['info'] = json_decode($info, true);
                return Response::json($return_array);
            default:
                return Response::json($return_array);
                break;
        }
    }

    function dataCheck($message, $id, $content, $step, $name, $action, $insert = true )
    {
        $action = (int)$action;
        if (empty($message)) {
            switch($action){
                case 1:
                    array_push($message, '您提交的数据已保存至数据库，即将进入下一步。');
                    break;
                case -1:
                    $user = DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->first();
                    if ($user->current_step > 1) {
                        if (!$this->canReturn($id) && $user->current_step == 6)
                            return Response::json(array('code' => 403, 'message' => ['您的申请正在处理中，无法返回编辑']));
                        DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->update(['current_step' => (int)$user->current_step - 1]);
                        return Response::json(array('code' => 200, 'message' => ['您即将返回至上一步']));
                    } else {
                        return Response::json(array('code' => 403, 'message' => ['您已经在第一步，无法再返回了']));
                    }
                    break;
                case 0:
                    array_push($message, '您提交的数据已保存至数据库！');
                    break;
                default:
                    abort(403);
                    break;
            }
            if ($insert) {
                DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->update([$name => json_encode($content), 'current_step' => $step + $action, 'edit_time' => date('y-m-d h:i:s')]);
            }
            return Response::json(array('code' => 200, 'message' => $message));
        } else {
            array_unshift($message, '非常抱歉，您提交的数据在以下部分存在问题：');
            return Response::json(array('code' => '403.1', 'message' => $message));
        }
    }

    function canReturn($id)
    {
        $info = DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->first();
        if ($info->status == true)
            return false;
        if (!is_null($info->submit_time))
            if (is_null($info->status_change_time))
                return false;
            else
                if (strtotime($info->status_change_time) < strtotime($info->submmit_time))
                    return false;
        return true;

    }

    function isEmpty($content)
    {
        return @(is_null($content) || empty($content));
    }

    function insertId($id)
    {
        DB::connection('mysql_alumni')->table('user_auth')->insert(['id' => $id]);
    }


    function emptyCheck($type, &$info, $name, &$message, $addMessage = true)
    {
        switch ($type) {
            case self::SCHOOL_NO:
                if ($this->isEmpty($info)) {
                    $info = (int)$info;
                    if ($addMessage)
                        array_push($message, '请选择您所就读的' . $name . '。');
                    return false;
                }
                break;
            case self::ENTER_YAER:
                if ($this->isEmpty($info)) {
                    if ($addMessage)
                        array_push($message, '请填写您' . $name . '的入学年份。');
                    return false;
                }
                break;
            case self::GRADUATED_YEAR:
                if ($this->isEmpty($info)) {
                    if ($addMessage)
                        array_push($message, '请填写您' . $name . '的毕业年份。');
                    return false;
                }
                break;
            case self::SCHOOL_NAME:
                if ($this->isEmpty($info)) {
                    if ($addMessage)
                        array_push($message, '请填写您所就读的' . $name . '全名。请不要使用任何简写。');
                    return false;
                }
                break;
            case self::CLASS_NO:
                if ($this->isEmpty($info)) {
                    if ($addMessage)
                        array_push($message, '请填写您的' . $name . '初中班级号。');
                    return false;
                }
                break;
            default:
                if ($this->isEmpty($info)) {
                    if ($addMessage)
                        array_push($message, '请填写您的' . $name . '。');
                    return false;
                }
        }
        return true;
    }

    function classNoCheck(&$info, $min, $max, $name, &$message)
    {
        $info = (int)($info);
        $valid = new Between(['min' => $min, 'max' => $max]);
        if (!$valid->isValid($info) || !is_integer($info))
            array_push($message, $name . '班级号不正确。');

    }


    function schoolYearCheck(&$enter_year, &$graduated_year, $minimum_year, $maximum_year, $interval, $remark, $name, &$message)
    {
        $enter_year = (int)($enter_year);
        $graduated_year = (int)($graduated_year);
        $valid = new Between(['min' => $minimum_year, 'max' => $maximum_year]);
        if (!$valid->isValid($enter_year) || !is_integer($enter_year))
            array_push($message, $name . '入学年份不正确。');
        unset($valid);
        $valid = new Between(['min' => $minimum_year + $interval, 'max' => $maximum_year + $interval]);
        if (!$valid->isValid($graduated_year) || !is_integer($graduated_year))
            array_push($message, $name . '毕业年份不正确。');
        if ($enter_year + $interval != $graduated_year && (@$this->isEmpty($remark)))
            array_push($message, $name . '毕业年份与入学年份不对应！如果有特殊情况，请在备注中注明。');
    }

    function structureCheck($info, $count, &$message)
    {
        if (count((array)$info) != $count)
            array_push($message, '您提交的信息存在结构性问题，请重试或解决上面提到的任何错误。如果此错误持续发生，请联系管理员。');
    }


    //auth data

    function authStep1($info)
    {
        /*
            JSON格式：
                username：用户名
                email：邮箱
                usedname：曾用名
                realname：真实姓名
                phone_domestic：中国手机号
                phone_international：外国手机号（含区号）［二选一］
                nickname：绰号
                english_name：英文名
                birthday：出生日期
                gender：性别
        */
        $message = array();
        @$this->emptyCheck(self::OTHER, $info->realname, '真实姓名', $message);
        if (mb_strlen($info->realname) >= 2 || mb_strlen($info->realname) <= 4) {
            $len = mb_strlen($info->realname);
            $width = mb_strwidth($info->realname);
            if ($len * 2 != $width)
                array_push($message, '真实姓名内容不符合要求。请输入2-4个中文字符');
        } else {
            array_push($message, '真实姓名内容不符合要求。请输入2-4个中文字符');
        }
        if (@$this->emptyCheck(self::OTHER, $info->phone_domestic, '手机号码（国内）', $message)) {
            if (!preg_match('/^1[34578]\d{9}$/', (string)($info->phone_domestic))) {
                array_push($message, '国内手机号码不正确！');
            }
        }
        if (@$this->emptyCheck(self::OTHER, $info->phone_international, '手机号码（国外）', $message, false)) {
            try {
                $phone_validate = \libphonenumber\PhoneNumberUtil::getInstance();
                $phone_number = $phone_validate->parse($info->phone_international, \libphonenumber\PhoneNumberFormat::INTERNATIONAL);
                if ($phone_validate->isValidNumber($phone_number)) {
                    array_push($message, '国外手机号码不正确！请检查国际区号或者手机号码是否正确。');
                }
            } catch (NumberParseException $e) {
                array_push($message, '国外手机号码不正确！请检查国际区号或者手机号码是否正确。');
            }
        }
        if (@$this->emptyCheck(self::OTHER, $info->nickname, '昵称', $message, false)) {
            $names = explode('，', $info->nickname);
            if (count($names) > 1) {
                array_push($message, '昵称分隔错误，请检查您的输入内容。');
            }
        }
        if (@$this->emptyCheck(self::OTHER, $info->english_name, '英文名', $message)) {
            $names = explode('，', $info->english_name);
            if (count($names) > 1) {
                array_push($message, '英文名分隔错误，请检查您的输入内容。');
            }
        }
        if (@$this->emptyCheck(self::OTHER, $info->birthday, '出生日期', $message)) {
            $validator = new Date(['format' => 'Y/m/d']);
            if (!$validator->isValid($info->birthday)) {
                array_push($message, '出生日期格式不符合要求。');
            }
            if (date('Y', strtotime($info->birthday)) + 15 > date('Y')) {
                array_push($message, '请在高一后填写此表格。');
            }
            if (date('Y', strtotime($info->birthday)) + 18 < 1963) {
                array_push($message, '您的年龄不符合最低入学要求。');
            }
        }
        if (@$this->emptyCheck(self::OTHER, $info->gender, '性别', $message)) {
            $info->gender = (int)($info->gender);
            $valid = new Between(['min' => self::GENDER_MALE, 'max' => self::GENDER_OTHER]);
            if (!$valid->isValid($info->gender))
                array_push($message, '性别不正确。');
        }
        if (@$this->emptyCheck(self::OTHER, $info->usedname, '曾用名', $message, false)) {
            if (mb_strlen($info->usedname) >= 2 || mb_strlen($info->usedname) <= 4) {
                $len = mb_strlen($info->usedname);
                $width = mb_strwidth($info->usedname);
                if ($len * 2 != $width)
                    array_push($message, '曾用名内容不符合要求。请输入2-4个中文字符');
            } else {
                array_push($message, '曾用名内容不符合要求。请输入2-4个中文字符');
            }
        }
        $this->structureCheck($info,10,$message);
        return $message;
    }

    function authStep2($info)
    {
        $message = array();
        /*
            JSON格式：
                primary_school_no：学校id
                primary_school_name：学校全名
                primary_school_enter_year：入学年份
                primary_school_graduated_year：毕业年份
                remark：备注
        */
        if (@$this->emptyCheck(self::SCHOOL_NO, $info->primary_school_no, '小学', $message)) {
            //两年毕业 ！！！
            switch ($info->primary_school_no) {

                case self::OTHER_PRIMARY:
                    @$this->emptyCheck(self::SCHOOL_NAME, $info->primary_school_name, '小学', $message);
                    $this->structureCheck($info, 3, $message);
                    break;
                case self::NFLS_PRIMARY_2:
                    @$this->emptyCheck(self::SCHOOL_NAME, $info->primary_school_name, '其他小学', $message);
                    @$passed = @$this->emptyCheck(self::ENTER_YAER, $info->primary_school_enter_year, '小学', $message);
                    @$passed = @$this->emptyCheck(self::GRADUATED_YEAR, $info->primary_school_graduated_year, '小学', $message);
                    if ($passed) {
                        $info->primary_school_enter_year = (int)($info->primary_school_enter_year);
                        $info->primary_school_graduated_year = (int)($info->primary_school_graduated_year);
                        @$this->schoolYearCheck($info->primary_school_enter_year, $info->primary_school_graduated_year, self::SCHOOL_START_YEAR, self::PRIMARY_END_YEAR, 2, $info->primary_remark, '小学', $message);
                    }
                    $this->structureCheck($info, 5, $message);
                    break;
                case self::NFLS_PRIMARY_4:
                    @$this->emptyCheck(self::SCHOOL_NAME, $info->primary_school_name, '其他小学', $message);
                    $passed = @$this->emptyCheck(self::ENTER_YAER, $info->primary_school_enter_year, '小学', $message);
                    $passed = @$this->emptyCheck(self::GRADUATED_YEAR, $info->primary_school_graduated_year, '小学', $message);
                    if ($passed) {
                        $info->primary_school_enter_year = (int)($info->primary_school_enter_year);
                        $info->primary_school_graduated_year = (int)($info->primary_school_graduated_year);
                        @$this->schoolYearCheck($info->primary_school_enter_year, $info->primary_school_graduated_year, self::SCHOOL_START_YEAR, self::PRIMARY_END_YEAR, 4, $info->primary_remark, '小学', $message);
                    }
                    $this->structureCheck($info, 5, $message);
                    break;
                default:
                    array_push($message, '小学信息不正确！请重新选择。');
                    break;
            }
        }
        return $message;
    }

    function authStep3($info)
    {
        $message = array();
        /*
            JSON格式：
                junior_school_no：学校id
                junior_school_name：学校全名
                junior_school_enter_year：入学年份
                junior_school_graduated_year：毕业年份
                junior_class：班级号
                remark：备注
        */
        if (@$this->emptyCheck(self::SCHOOL_NO, $info->junior_school_no, '初中', $message)) {
            switch ($info->junior_school_no) {
                case self::OTHER_JUNIOR:
                    @$this->emptyCheck(self::SCHOOL_NAME, $info->junior_school_name, '初中', $message);
                    $this->structureCheck($info, 3, $message);
                    break;
                case self::NFLS_JUNIOR:
                    @$passed = $this->emptyCheck(self::ENTER_YAER, $info->junior_school_enter_year, '初中', $message);
                    @$passed = $this->emptyCheck(self::GRADUATED_YEAR, $info->junior_school_graduated_year, '初中', $message);
                    @$passed = $this->emptyCheck(self::CLASS_NO, $info->junior_class, '初中', $message);
                    if ($passed) {
                        @$this->schoolYearCheck($info->junior_school_enter_year, $info->junior_school_graduated_year, self::SCHOOL_START_YEAR, date('Y') - 6, 3, $info->junior_remark, '初中', $message);
                        @$this->classNoCheck($info->junior_class, 1, 12, '初中', $message);
                    }
                    $this->structureCheck($info, 5, $message);
                    break;
                default:
                    array_push($message, '初中信息不正确！请重新选择。');
                    break;
            }
        }
        return $message;
    }

    function authStep4($info)
    {
        $message = array();
        /*
            JSON格式：
                senior_school_no：学校id
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
        if (@$this->isEmpty($info->senior_school_no))
            array_push($message, '请选择您所就读的高中。');
        else {
            switch ($info->senior_school_no) {
                case self::OTHER_SENIOR:
                    @$this->emptyCheck(self::SCHOOL_NAME, $info->senior_school_name, '高中', $message);
                    $this->structureCheck($info, 3, $message);
                    break;
                case self::NFLS_SENIOR_GENERAL:
                    @$passed = $this->emptyCheck(self::ENTER_YAER, $info->senior_school_enter_year, '高中', $message);
                    @$passed = $this->emptyCheck(self::GRADUATED_YEAR, $info->senior_school_graduated_year, '高中', $message);
                    @$passed = $this->emptyCheck(self::CLASS_NO, $info->senior_class_11, '高一上', $message);
                    @$passed = $this->emptyCheck(self::CLASS_NO, $info->senior_class_12, '高一下', $message);
                    @$passed = $this->emptyCheck(self::CLASS_NO, $info->senior_class_21, '高二上', $message);
                    @$passed = $this->emptyCheck(self::CLASS_NO, $info->senior_class_22, '高二下', $message);
                    @$passed = $this->emptyCheck(self::CLASS_NO, $info->senior_class_31, '高三上', $message);
                    @$passed = $this->emptyCheck(self::CLASS_NO, $info->senior_class_32, '高三下', $message);
                    if ($passed) {
                        @$this->schoolYearCheck($info->senior_school_enter_year, $info->senior_school_graduated_year, self::SCHOOL_START_YEAR, date('Y') - 3, 3, $info->senior_remark, '高中', $message);//
                        //self::
                        $this->classNoCheck($info->senior_class_11, 1, 8, '高一上', $message);
                        $this->classNoCheck($info->senior_class_12, 0, 8, '高一下', $message);
                        $this->classNoCheck($info->senior_class_21, 0, 8, '高二上', $message);
                        $this->classNoCheck($info->senior_class_22, 0, 8, '高二下', $message);
                        $this->classNoCheck($info->senior_class_31, 0, 8, '高三上', $message);
                        $this->classNoCheck($info->senior_class_32, 0, 8, '高三下', $message);
                    }
                    $this->structureCheck($info, 10, $message);
                    break;
                /*
                case NFLS_SENIOR_AUSTRALIA:
                    $passed = $this->emptyCheck(self::ENTER_YAER,$info->senior_school_enter_year,'高中',$message);
                    $passed = $this->emptyCheck(self::GRADUATED_YEAR,$info->senior_school_graduated_year,'高中',$message);
                    if($passed)
                        @$this->schoolYearCheck($info->senior_school_enter_year,$info->senior_school_graduated_year,self::SCHOOL_START_YEAR,date('Y') - 3,3,$info->remark,'高中',$message);
                    $this->structureCheck($info,4,$message);
                    break;
                */
                case self::NFLS_SENIOR_ALEVEL:
                    @$passed = $this->emptyCheck(self::ENTER_YAER, $info->senior_school_enter_year, '高中', $message);
                    @$passed = $this->emptyCheck(self::GRADUATED_YEAR, $info->senior_school_graduated_year, '高中', $message);
                    if ($passed) {
                        if ($info->senior_school_enter_year > self::ALEVEL_2_START_YEAR) {
                            @$this->schoolYearCheck($info->senior_school_enter_year, $info->senior_school_graduated_year, self::ALEVEL_2_START_YEAR, date('Y') - 3, 3, $info->remark, '高中', $message);
                            @$this->classNoCheck($info->senior_class, 1, 2, '高中', $message);
                        } else {
                            @$this->schoolYearCheck($info->senior_school_enter_year, $info->senior_school_graduated_year, self::ALEVEL_4_START_YEAR, date('Y') - 3, 3, $info->remark, '高中', $message);
                            @$this->classNoCheck($info->senior_class, 1, 4, '高中', $message);
                        }
                    }
                    $this->structureCheck($info, 5, $message);
                    break;
                case self::NFLS_SENIOR_IB:
                    @$passed = $this->emptyCheck(self::ENTER_YAER, $info->senior_school_enter_year, '高中', $message);
                    @$passed = $this->emptyCheck(self::GRADUATED_YEAR, $info->senior_school_graduated_year, '高中', $message);
                    if ($passed) {
                        @$this->schoolYearCheck($info->senior_school_enter_year, $info->senior_school_graduated_year, self::IB_START_YEAR, date('Y') - 3, 3, $info->remark, '高中', $message);
                        @$this->classNoCheck($info->senior_class, 1, 2, '高中', $message);
                    }
                    $this->structureCheck($info, 5, $message);
                    break;
                case self::NFLS_SENIOR_BCA:
                    @$passed = $this->emptyCheck(self::ENTER_YAER, $info->senior_school_enter_year, '高中', $message);
                    @$passed = $this->emptyCheck(self::GRADUATED_YEAR, $info->senior_school_graduated_year, '高中', $message);
                    if ($passed) {
                        @$this->schoolYearCheck($info->senior_school_enter_year, $info->senior_school_graduated_year, self::BCA_START_YEAR, date('Y') - 3, 3, $info->remark, '高中', $message);
                        @$this->classNoCheck($info->senior_class, 1, 6, '高中', $message);
                    }
                    $this->structureCheck($info, 5, $message);
                    break;
                default:
                    array_push($message, '高中信息不正确！请重新选择。');
                    break;
            }
        }
        return $message;
    }


    function authStep5($id)
    {
        $return_array = array();
        $user = DB::connection('mysql_alumni')->table('user_auth')->where('id', $id)->first();
        $birth_year = (int)mb_substr(json_decode($user->auth_info,true)['birthday'],0,4);
        $primary=json_decode($user->primary_school);
        $junior=json_decode($user->junior_school);
        $senior=json_decode($user->senior_school);
        $p_graduate = 0;
        $j_enter = 0;
        $j_graduate = 0;
        $s_enter = 0;
        if($primary->primary_school_no > 0){
            if(-$birth_year + $primary->primary_school_graduated_year > 15)
                array_push($return_array,"小学毕业年份与生日差大于15年！");
            if(-$birth_year + $primary->primary_school_graduated_year < 12)
                array_push($return_array,"小学毕业年份与生日差小于12年！");
            $p_graduate = $primary->primary_school_graduated_year;
        }
        if($junior->junior_school_no > 0){
            if(-$birth_year + $junior->junior_school_graduated_year > 18)
                array_push($return_array,"初中毕业年份与生日差大于18年！");
            if(-$birth_year + $junior->junior_school_graduated_year < 15)
                array_push($return_array,"初中毕业年份与生日差小于15年！");
            $j_enter = $junior->junior_school_enter_year;
            $j_graduate = $junior->junior_school_graduated_year;
        }
        if($senior->senior_school_no > 0){
            if(-$birth_year + $senior->senior_school_graduated_year > 21)
                array_push($return_array,"高中毕业年份与生日差大于21年！");
            if(-$birth_year + $senior->senior_school_graduated_year < 18)
                array_push($return_array,"高中毕业年份与生日差小于18年！");
            $s_enter = $senior->senior_school_enter_year;
        }
        if($p_graduate!=0 && $j_enter!=0 && ($j_enter - $p_graduate > 1))
            array_push($return_array,"小学毕业与初中入学年份相差超过一年！");
        if($j_graduate!=0 && $s_enter!=0 && ($s_enter - $j_graduate > 1))
            array_push($return_array,"初中毕业与高中入学年份相差超过一年！");
        if($p_graduate!=0 && $j_enter!=0 && ($j_enter < $p_graduate))
            array_push($return_array,"初中入学年份小于小学毕业年份！");
        if($j_graduate!=0 && $s_enter!=0 && ($s_enter < $j_graduate))
            array_push($return_array,"高中入学年份小于初中毕业年份！");
        if($j_graduate==0 && $p_graduate==0 && $s_enter==0)
            array_push($return_array,"本服务仅限在南外校友使用！");


        //$user->
        return $return_array;

    }

    function authStep6($info)
    {
        $message = array();
        $passed = false;
        $grid_count = 0;
        if(@!$this->isEmpty($info->college) && $info->college == true)
            $passed = $this->collegeInfoCheck("college",$info,$message,"专科",$grid_count);
        if(@!$this->isEmpty($info->undergraduate) && $info->undergraduate == true)
            $passed = $this->collegeInfoCheck("undergraduate",$info,$message,"本科",$grid_count);
        if(@!$this->isEmpty($info->master) && $info->master == true)
            $passed = $this->collegeInfoCheck("master",$info,$message,"硕士",$grid_count);
        if(@!$this->isEmpty($info->doctor) && $info->doctor == true)
            $passed = $this->collegeInfoCheck("doctor",$info,$message,"博士",$grid_count);
        if(@!$this->isEmpty($info->other) && $info->other == true)
            $passed = $this->collegeInfoCheck("other",$info,$message,"其他",$grid_count);
        if(!$passed)
            array_push($message,"请至少选择一个进行填写！");
        $this->structureCheck($info,$grid_count+6,$message);
        return $message;
    }

    function collegeInfoCheck($index,$info,&$message,$name,&$count){
        //array_push($message,"1");
        $min_year = 1900;
        $max_year = 2100;
        foreach($info as $key=>$value){
            switch($key){
                case $index."_start":
                    $count = $count + 4;
                    if (@$this->isEmpty($value)) {
                        array_push($message, $name . '入学年份未填写。');
                    }
                    else {
                        $value = (int)$value;
                        $valid = new Between(['min' => $min_year, 'max' => $max_year]);
                        if (!$valid->isValid($value) || !is_integer($value))
                            array_push($message, $name . '入学年份不正确。');
                        else
                            $start_year = $value;
                    }
                    break;
                case $index."_end":
                    if (@$this->isEmpty($value)) {
                        array_push($message, $name . '毕业年份未填写。');
                    }
                    else {
                        $value = (int)$value;
                        $valid = new Between(['min' => $min_year, 'max' => $max_year]);
                        if (!$valid->isValid($value) || !is_integer($value))
                            array_push($message, $name . '毕业年份不正确。');
                        else
                            $end_year = $value;
                    }
                    break;
                case $index."_major":
                    if (@$this->isEmpty($value))
                        array_push($message, $name . '专业方向未填写。');
                    break;
                case $index."_school":
                    if (@$this->isEmpty($value))
                        array_push($message, $name . '就读院校未填写。');
                    break;
                case $index."_type":
                    if (@$this->isEmpty($value) && $index == "other") {
                        array_push($message, $name . '院校类型未填写。');
                        $count++;
                    }
                    break;
            }
        }
        if(@!is_null($start_year) && !is_null($end_year)){
            if($start_year>$end_year)
                array_push($message,$name . "入学年份大于毕业年份。");
        }
        return true;
    }

    function authStep7($info){
        $message = array();
        $this->structureCheck($info,1,$message);
        if(is_null($info->work_info))
            array_push($message,"您提交的信息存在结构性问题，请重试或解决上面提到的任何错误。如果此错误持续发生，请联系管理员。");
        return $message;
    }

    function authStep8($info){
        $message = array();
        $contact_count = 0;
        $content_count = 0;
        $this->structureCheck($info,16,$message);
        if(@$this->isEmpty($info->personal_info))
            array_push($message,"请填写自我介绍。");
        foreach($info as $key=>$value){
            switch($key){
                case "wechat":
                case "qq":
                case "weibo":
                case "telegram":
                case "whatsapp":
                case "skype":
                case "viber":
                case "google_talk":
                case "youtube":
                case "twitter":
                case "facebook":
                case "vimeo":
                case "instagram":
                case "snapchat":
                case "groupme":
                    $contact_count++;
                    if(@!$this->isEmpty($value))
                        $content_count++;
                    break;
                default:
                    break;
            }
        }
        if($content_count == 0)
            array_push($message,"请至少填写一个联系方式。");
        return $message;
    }

}

