<?php
/**
 * Created by PhpStorm.
 * User: Rickliu
 * Date: 10/2/17
 * Time: 7:14 PM
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Response;
use Cookie;

class GameListController extends Controller
{


    function test(){
        $message = "We're Mr Tunnel's Lonely Hearts Computing Club, We hope you will enjoy the bugs";
        return Response::json(array("code"=>200,"info"=>$message));
    }

    function getList(){
        DB::connection("mysql_user")->table("user_list")->first();
        $game_name = DB::connection("mysql_game")->table("_list")->get();
        $count = 0;
        foreach ($game_name as $name) {
            $info[$count]['id'] = $name->id;
            $info[$count]['name'] = $name->name;
            $info[$count]['description'] = $name->description;
            $info[$count]['icon'] = $name->icon;
            $info[$count]['url'] = $name->url;
            $count++;
        }
        $json_mes['code'] = 200;
        $json_mes['status'] = "succeed";
        $json_mes['info'] = $info;
        return Response::json($json_mes, 200);
    }

}
