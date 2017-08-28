<?php

namespace App\Http\Controllers;

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
    function listUniversities(Request $request){
        if($request->isMethod("POST")){
            if($request->has("startFrom")){
                $startWith = (int)($request->input("startFrom"));
            }else{
                $startWith = 0;
            }
            if($request->has("limit")){
                $limit = max(10,min(30,$request->input("limit")));
            } else {
                $limit = 10;
            }
            $enabled = true;
            if($request->has("enabled")){
                if(UserCenterController::checkAdmin(Cookie::get("token"))){
                    $enabled = (bool)$request->has("enabled");
                }
            }
            if(!$request->has("name")){
                $result = DB::connection("mysql_alumni")
                    ->table("universities")
                    ->where(["isEnabled"=>$enabled])
                    ->limit($limit)
                    ->offset($startWith)
                    ->select("id","name","shortName","chineseName","chineseShortName","country","comment")
                    ->get();
            } else {
                $result = DB::connection("mysql_alumni")
                    ->table("universities")
                    ->where(["isEnabled"=>$enabled])
                    ->where(function ($query) use($request) {
                        $query->where("name","like","%".$request->input("name")."%")
                            ->orWhere("shortName","like","%".$request->input("name")."%")
                            ->orWhere("chineseName","like","%".$request->input("name")."%")
                            ->orWhere("chineseShortName","like","%".$request->input("name")."%");
                    })
                    ->limit($limit)
                    ->offset($startWith)
                    ->select("id","name","shortName","chineseName","chineseShortName","country","comment")
                    ->get();
            }
            return Response::json(array("code"=>200,"info"=>$result));
        }
    }

    function addUniversity(Request $request){
        if($request->isMethod("POST") && $request->has(["country","name"])){
            $id = UserCenterController::GetUserId(Cookie::get("token"));
            if(UserCenterController::checkAdmin($id)){
                $enable = true;
            } else {
                $enable = false;
            }
            DB::connection("mysql_alumni")->table("universities")->insert([
                "name"=>$request->input("name"),
                "shortName"=>$request->input("shortName"),
                "chineseName"=>$request->input("chineseName"),
                "chineseShortName"=>$request->input("chineseShortName"),
                "country"=>$request->input("country"),
                "added_by"=>$id,
                "isEnabled"=>$enable]);
        }
    }

    function deleteUniversity(Request $request){

    }

    function editUniversity(Request $request){

    }
}
