<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Cookie;
use Illuminate\Pagination\Paginator;
use Response;
use Illuminate\Support\Facades\DB;

class StudentsListController extends Controller
{
    const INFO = "您可以在此处根据您的姓名查询对应班级信息，快速填写认证表格。数据库截止到2016届。";
    function getInfo(){
        return Response::json(array("code"=>200,"info"=>self::INFO."您在24小时内最多可查询 ".$this->getUserLimit(UserCenterController::GetUserId(Cookie::get("token")))."次。（注：请不要多次点击查询按钮）"));
    }
    function getClassDetail($id){
        $class = DB::connection("mysql_alumni")->table("classes")->where(["id"=>$id])->first();
        return @array("year"=>$class->year,"class"=>$class->class,"type"=>$class->type,"comment"=>$class->comment);
    }
    function numToChn($num){
        if($num == -1)
            return "--";
        $cn_arr = array("〇","一","二","三","四","五","六","七","八","九");
        $str = "";
        while ($num>10){
            $str = $cn_arr[$num % 10] . $str;
            $num = $num / 10;
        }
        return $cn_arr[$num] . $str;
    }
    function getReadableClass($array){
        $str = $this->decodeClassType($array["type"]).$this->numToChn($array["year"])."届".$array["class"]."班";
        if(@!is_null($array["comment"]) && $array["comment"] != ""){
            $str = $str . "（备注：" . $array["comment"] . "）";
        }
        return $str;
    }
    function decodeClassType($str){
        switch($str){
            case "GeneralJunior":
                return "普通初中课程";
            case "GeneralSenior":
                return "普通高中课程";
            case "ALevelSenior":
                return "A-Level国际课程";
            case "IBSenior":
                return "IB国际课程";
            case "UNSW":
                return "新南威尔士大学预科课程";
            case "TeacherSenior":
                return "中师课程";
            case "Japanese":
                return "日语代培课程";
            case "StandardEducationSenior":
                return "基础教育高中课程";
            case "StandardEducationJunior":
                return "基础教育初中课程";
            case "StandardEducationPrimary":
                return "基础教育小学课程";
            case "BCASenior":
                return "中加国际课程";
            default:
                return "未知，请联系管理员";
        }
    }

    function getUserLimit($id){
        return 9999999;
    }
    function addQueryTime($id){
        $user = DB::connection("mysql_alumni")->table("user_auth")->where(["id"=>$id])->first();
        if(@!is_null(json_decode($user->query_time,true))){
            $old_times = json_decode($user->query_time, true);
            $times = array();
            foreach($old_times as $time){
                if(strtotime($time) > strtotime('-24 hours')){
                    array_push($times,$time);
                }
            }
            if(count($times) > $this->getUserLimit($id)){
                return false;
            } else {
                array_push($times, date('Y-m-d h:i:s'));
            }
        } else {
            $times = array(date('Y-m-d h:i:s'));
        }
        DB::connection("mysql_alumni")->table("user_auth")->where(["id"=>$id])->update(["query_time"=>json_encode($times)]);
        return true;
    }

    function getNameList(Request $request){
        $id = CertificationController::getUser(Cookie::get("token"));
        if($request->has(["name","session","captcha"])){
            if(!UserCenterController::ConfirmCaptcha($request->input("session"), $request->input("captcha"), "nameQuery"))
                return array("code"=>403,"info"=>"验证码无效或不正确");
            if($this->addQueryTime($id)) {
                $array = array();
                $names = DB::connection("mysql_alumni")->table("students")->where(["name" => $request->input("name"), "used" => 0])->get();
                foreach ($names as $name) {
                    array_push($array, array("name" => $this->getReadableClass($this->getClassDetail($name->class_id)), "id" => $name->id));
                }
                return Response::json(array("code"=>200,"info"=>$array));
            } else {
                return Response::json(array("code"=>403,"info"=>"您已超出24小时内查询限制，请稍候再试"));
            }
        } else {
            abort(404);
        }
    }

    function useName(Request $request){
        $id = CertificationController::getUser(Cookie::get("token"));
        $names = DB::connection("mysql_alumni")->table("students")->where(["used" => $id])->get();
        if(count($names)==3){
            abort(403);
        }
        if($request->has(["id","name"])) {
            DB::connection("mysql_alumni")->table("students")->where(["id" => $request->input("id"), "used" => 0, "name"=>$request->input("name")])->update(["used"=>$id]);
            return Response::json(array("code" => 200));
        } else {
            abort(404);
        }
    }

    function unuseName(Request $request){
        $id = CertificationController::getUser(Cookie::get("token"));
        if($request->has(["id","name"])) {
            DB::connection("mysql_alumni")->table("students")->where(["id" => $request->input("id"), "used" => $id, "name"=>$request->input("name")])->update(["used"=>0]);
            return Response::json(array("code" => 200));
        } else {
            abort(404);
        }
    }

    function getUsedName(Request $request){
        $id = CertificationController::getUser(Cookie::get("token"));
        $array = array();
        $names = DB::connection("mysql_alumni")->table("students")->where(["name" => $request->input("name"), "used" => $id])->get();
        foreach ($names as $name) {
            array_push($array, array("name" => $this->getReadableClass($this->getClassDetail($name->class_id)), "id" => $name->class_id));
        }
        return Response::json(array("code"=>200,"info"=>$array));
    }
}
