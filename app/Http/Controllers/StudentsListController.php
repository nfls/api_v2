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
            default:
                return "未知，请联系管理员";
        }
    }
    function getNameList(Request $request){
        $id = $this->getUser(Cookie::get('token'));
        if($request->has(["name","session","captcha"])){
            //if(!UserCenterController::ConfirmCaptcha($request->input("session"), $request->input("captcha"), "nameQuery"))
            //    return array("status"=>"failure","message"=>"验证码无效或不正确");
            $array = array();
            $names = DB::connection("mysql_alumni")->table("students")->where(["name"=>$request->input("name"),"used"=>false])->get();
            foreach($names as $name){
                array_push($array,$this->getReadableClass($this->getClassDetail($name->class_id)));
            }
            return Response::json($array,200);
        } else {
            abort(404);
        }
    }
}
