<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Response;
use Illuminate\Support\Facades\DB;

class AlumniWebsiteController extends Controller
{
    function getPostList(){
        return Response::json(array("code"=>200,"info"=>DB::connection("mysql_alumni")->table("typecho_contents")->where(["type"=>"post"])->select('cid','title')->get()));
    }

    function getDetailPost(Request $request){
        if($request->has("cid") && $request->only("cid")){
            return Response::json(array("code"=>200,"info"=>DB::connection("mysql_alumni")->table("typecho_contents")->where(["type"=>"post","cid"=>$request->input("cid")])->select('text')->get()));
        }
    }
}
