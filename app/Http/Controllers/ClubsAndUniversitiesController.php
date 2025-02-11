<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\PaginationServiceProvider;
use App\Http\Controllers\UserCenterController;
use Cookie;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Routing\ResponseFactory;
use Response;

class ClubsAndUniversitiesController extends Controller
{

    function getIntro(){
        $message = "1. 在本页，您可以查询，修改或添加大学<br/>" .
                   "2. 官方数据来源于中国教育部，美国NCES，加拿大AUCC，日本文部科学省，其他地区为非官方数据，可能会有缺漏<br/>" .
                   "3. 上述官方数据中，大陆地区学校名为中文，日本地区为日文，加拿大为英文或法文，美国地区为英文，其余地区均为英语，搜索时请使用对应语言<br/>" .
                   "4. 您可以通过学校的名称搜索，如果有人为该校添加过中文或是简写后，您也可以通过此类信息搜索到<br/>" .
                   "5. 如果您是第一个选择该学校的，您需要帮助我们完善学校信息，如当地语言的简称，中文翻译，中文简称等等<br/>" .
                   "6. 如果学校的相关信息有多个的话，可以用空格分隔<br/>" .
                   "7. 如果您搜索不到您的学校，请选择添加大学，并按照表格完善相关信息，方便二次使用，学校名称一经添加无法修改，请慎重！<br/>".
                   "8. 所有带英文的China字样的大学均指的是台湾地区的大学<br/>".
                   "9. 您必须要通过实名认证表格打开本页才能修改您的大学信息，否则只能修改大学的名称等信息<br/>".
                   "10. 如果您不想修改相关信息，请直接关闭页面或选择不保存返回，点击返回将默认将保存您的修改<br/>".
                   "11. 请先搜索确认无法找到学校后再添加！";
        return Response::json(array("code"=>200,"info"=>$message));
    }

    function listUniversities(Request $request)
    {
        if ($request->has("startFrom")) {
            $startWith = (int)($request->input("startFrom"));
        } else {
            $startWith = 0;
        }
        if ($request->has("limit")) {
            $limit = max(10, min(30, $request->input("limit")));
        } else {
            $limit = 20;
        }

        $result = DB::connection("mysql_alumni")
            ->table("universities")
            ->limit($limit)
            ->offset($startWith)
            ->select("id", "name");

        if ($request->has("name"))
            $result->where(function ($query) use ($request) {
                $query->where("name", "like", "%" . $request->input("name") . "%")
                    ->orWhere("shortName", "like", "%" . $request->input("name") . "%")
                    ->orWhere("chineseName", "like", "%" . $request->input("name") . "%")
                    ->orWhere("chineseShortName", "like", "%" . $request->input("name") . "%");
            });

        return Response::json(array("code" => 200, "info" => $result->get()));
    }

    function getAUniversity(Request $request){
        if($request->has("id")){
            $info = DB::connection("mysql_alumni")->table("universities")->where(["id"=>$request->input("id")])->first();
            $info->added_by = UserCenterController::GetUserNickname($info->added_by);
            return Response::json(array("code"=>200,"info" => $info));
        }

    }

    function editUniversity(Request $request)
    {
        $id = UserCenterController::GetUserId(Cookie::get("token"));

        if(UserCenterController::checkAdmin($id) && $request->has("enabled")){
            $enabled = $request->input("enabled");
        } else {
            $enabled = DB::connection("mysql_alumni")->table("universities")->where(["id"=>$request->input("id")])->first()->isEnabled;
        }
        if ($request->has(["id","country", "name"])) {
            LogController::writeLog("univeristy.edit","修改了id=".$request->input("id")."的大学");
            DB::connection("mysql_alumni")->table("universities")->where(["id"=>$request->input("id"),"name"=>$request->input("name")])->update([
                "shortName" => $request->input("shortName"),
                "chineseName" => $request->input("chineseName"),
                "chineseShortName" => $request->input("chineseShortName"),
                "comment" => $request->input("comment"),
                "country" => $request->input("country"),
                "isEnabled" => $enabled]);
            return Response::json(array("code" => 200, "info" => DB::connection("mysql_alumni")->table("universities")->where(["id"=>$request->input("id")])->get()));
        }

    }

    function addUniversity(Request $request){
        $id = UserCenterController::GetUserId(Cookie::get("token"));
        if ($request->has(["country", "name"])) {
            LogController::writeLog("univeristy.add","添加了name=".$request->input("name")."的大学");
            DB::connection("mysql_alumni")->table("universities")->insert([
                "name" => $request->input("name"),
                "shortName" => $request->input("shortName"),
                "chineseName" => $request->input("chineseName"),
                "chineseShortName" => $request->input("chineseShortName"),
                "comment" => $request->input("comment"),
                "country" => $request->input("country"),
                "added_by" => $id,
                "isEnabled" => false]);
            return Response::json(array("code" => 200, "info" => DB::connection("mysql_alumni")->table("universities")->where(["name"=>$request->input("name")])->orderBy('id', 'desc')->first()));
        }
    }

    function deleteUniversity(Request $request)
    {
        $id = UserCenterController::GetUserId(Cookie::get("token"));
        if(UserCenterController::checkAdmin($id)){
            DB::connection("mysql_alumni")->table("universities")->where(["id"=>$request->input("id")])->delete();
            return array("code"=>200);
        } else {
            abort(403);
        }
    }

    function getClubIntro(){
        $message = "1. 在本页，您可以查询，修改或添加社团<br/>" .
            "2. 此处社团不区分本部、国际部，也不区分成立的年份等等，只是作为显示相关信息使用<br/>" .
            "3. 内容及性质类似的社团请不要重复添加，否则将被管理员删除并合并，添加社团请务必填写备注（如简要的活动内容等等）<br/>" .
            "4. 您也可以添加非官方注册的社团，但为了您和我们的人身安全，严禁添加膜ha社等明显不符合相关国内规定的社团<br/>" .
            "5. 您必须要通过实名认证表格打开本页才能修改您的社团信息，否则只能修改社团的名称等信息<br/>".
            "6. 如果您不想修改相关信息，请直接关闭页面或选择不保存返回，点击返回将默认将保存您的修改";
        return Response::json(array("code"=>200,"info"=>$message));
    }

    function listClubs(Request $request){
        $result = DB::connection("mysql_alumni")
            ->table("clubs")
            ->select("id", "name");
        if ($request->has("name"))
            $result->where("name", "like", "%" . $request->input("name") . "%");
        return Response::json(array("code" => 200, "info" => $result->get()));
    }

    function getAClub(Request $request){
        if($request->has("id")){
            $info = DB::connection("mysql_alumni")->table("clubs")->where(["id"=>$request->input("id")])->first();
            $info->added_by = UserCenterController::GetUserNickname($info->added_by);
            return Response::json(array("code"=>200,"info" => $info));
        }

    }

    function editAClub(Request $request)
    {
        $id = UserCenterController::GetUserId(Cookie::get("token"));

        if(UserCenterController::checkAdmin($id) && $request->has("enabled")){
            $enabled = $request->input("enabled");
        } else {
            $enabled = DB::connection("mysql_alumni")->table("clubs")->where(["id"=>$request->input("id")])->first()->isEnabled;
        }
        if ($request->has(["id", "name"])) {
            LogController::writeLog("club.edit","修改了id=".$request->input("id")."的社团");
            DB::connection("mysql_alumni")->table("clubs")->where(["id"=>$request->input("id")])->update([
                "name"=>$request->input("name"),
                "comment" => $request->input("comment")]);
            return Response::json(array("code" => 200, "info" => DB::connection("mysql_alumni")->table("clubs")->where(["id"=>$request->input("id")])->get()));
        }

    }

    function addClub(Request $request){
        $id = UserCenterController::GetUserId(Cookie::get("token"));
        if ($request->has(["name"])) {
            LogController::writeLog("club.add","添加了name=".$request->input("name")."的社团");
            DB::connection("mysql_alumni")->table("clubs")->insert([
                "name" => $request->input("name"),
                "comment" => $request->input("comment"),
                "added_by" => $id,
                "isEnabled" => false]);
            return Response::json(array("code" => 200, "info" => DB::connection("mysql_alumni")->table("clubs")->where(["name"=>$request->input("name")])->orderBy('id', 'desc')->first()));
        }
    }

    function deleteAClub(Request $request)
    {
        $id = UserCenterController::GetUserId(Cookie::get("token"));
        if(UserCenterController::checkAdmin($id)){
            DB::connection("mysql_alumni")->table("clubs")->where(["id"=>$request->input("id")])->delete();
            return array("code"=>200);
        } else {
            abort(403);
        }
    }

}
