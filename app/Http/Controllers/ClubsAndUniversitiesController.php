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
        $message = "在本页，您可以查询，修改或添加大学<br/>" .
                   "官方数据来源于中国教育部，美国NCES，加拿大AUCC，日本文部科学省，其他地区为非官方数据，可能会有缺漏<br/>" .
                   "上述官方数据中，大陆地区学校名为中文，日本地区为日文，加拿大为英文或法文，美国地区为英文，其余地区均为英语，搜索时请使用对应语言（别告诉我你自己学校名字都不会写~）<br/>" .
                   "您可以通过学校的名称搜索，如果有人为该校添加过中文或是简写后，您也可以通过此类信息搜索到<br/>" .
                   "如果您是第一个选择该学校的，您需要帮助我们完善学校信息，如当地语言的简称，中文翻译，中文简称等等<br/>" .
                   "如果您搜索不到您的学校，请选择添加大学，并按照表格完善相关信息，方便二次使用，添加的学校需要审核后才会显示在您的个人信息里<br/>";
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
            $limit = 10;
        }
        $enabled = true;
        if ($request->has("enabled")) {
            if (UserCenterController::checkAdmin(Cookie::get("token"))) {
                $enabled = (bool)$request->has("enabled");
            }
        }

        $result = DB::connection("mysql_alumni")
            ->table("universities")
            ->where(["isEnabled" => $enabled])
            ->limit($limit)
            ->offset($startWith)
            ->select("id", "name", "shortName", "chineseName", "chineseShortName", "country", "comment");

        if (!$request->has("name"))
            $result->where(function ($query) use ($request) {
                $query->where("name", "like", "%" . $request->input("name") . "%")
                    ->orWhere("shortName", "like", "%" . $request->input("name") . "%")
                    ->orWhere("chineseName", "like", "%" . $request->input("name") . "%")
                    ->orWhere("chineseShortName", "like", "%" . $request->input("name") . "%");
            });

        return Response::json(array("code" => 200, "info" => $result->get()));
    }

    function getAUniversity(Request $request){
        if($request->has("id"))
            return Response::json(array("code" => 200, "info" => DB::connection("mysql_alumni")->table("universities")->where(["id"=>$request->input("id")])->get()));
    }

    function editUniversity(Request $request)
    {
        $id = UserCenterController::GetUserId(Cookie::get("token"));
        if(UserCenterController::checkAdmin($id) && $request->has("enabled")){
            $enabled = $request->input("enabled");
        } else {
            $enabled = DB::connection("mysql_alumni")->table("universities")->where(["id"=>$request->input("id")])->get()->isEnabled;
        }
        if ($request->has(["id","country", "name"])) {
            DB::connection("mysql_alumni")->table("universities")->where(["id"=>$request->input("id")])->update([
                "name" => $request->input("name"),
                "shortName" => $request->input("shortName"),
                "chineseName" => $request->input("chineseName"),
                "chineseShortName" => $request->input("chineseShortName"),
                "country" => $request->input("country"),
                "isEnabled" => $enabled]);
            return Response::json(array("code" => 200, "info" => DB::connection("mysql_alumni")->table("universities")->where(["id"=>$request->input("id")])->get()));
        }

    }

    function addUniversity(Request $request){
        $id = UserCenterController::GetUserId(Cookie::get("token"));
        if(UserCenterController::checkAdmin($id) && $request->has("enabled")){
            $enabled = $request->input("enabled");
        } else {
            $enabled = false;
        }
        if ($request->has(["id","country", "name"])) {
            DB::connection("mysql_alumni")->table("universities")->insert([
                "name" => $request->input("name"),
                "shortName" => $request->input("shortName"),
                "chineseName" => $request->input("chineseName"),
                "chineseShortName" => $request->input("chineseShortName"),
                "country" => $request->input("country"),
                "added_by" => $id,
                "isEnabled" => $enabled]);
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

}
