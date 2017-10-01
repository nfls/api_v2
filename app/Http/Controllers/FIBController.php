<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Response;
use Cookie;

class FIBController extends Controller
{
    function requestHandler(Request $request, $type)
    {
        Log::Info(Cookie::get("token").":".$type);
        $id = $this->getUser(Cookie::get("token"));
        switch ($type) {
            case "rank":
                if($request->isMethod("get")){
                    $info = $this->getRank($id);
                }else if($request->isMethod("post") && $request->has("score")){
                    $info = $this->updateScore($id,$request->input("score"));
                }
                break;
            default:
                break;
        }
        $json_mes = array();
        if (!@is_int($info) && (@is_null($info) || @empty($info))) {
            $json_mes['code'] = 403;
            $json_mes['status'] = "error";
            return Response::json($json_mes, 403);
        } else {
            $json_mes['code'] = 200;
            $json_mes['status'] = "succeed";
            $json_mes['info'] = $info;
            return Response::json($json_mes, 200);
        }

    }

    function getUser($token)
    {

        $id = UserCenterController::GetUserId($token);
        $this->checkScoreExpiration();
        $user = DB::connection('mysql_game')->table('fib_userdata')->where('id', $id)->first();
        if (is_null($user))
            DB::connection('mysql_game')->table('fib_userdata')->insert(['id' => $id]);
        return $id;
    }

    function checkScoreExpiration(){
        DB::connection("mysql_game")->table("fib_userdata")->where(["lastPlayed","<",date('Y-m-d H:i:s',strtotime("-1 week"))])->delete();
    }

    function getRank($id,$retrieve = true){
        $user = DB::connection("mysql_game")->table("fib_userdata")->where(["id"=>$id])->first();
        $count = DB::connection("mysql_game")->table("fib_userdata")->where("score",">",$user->score)->get();
        $count = count($count);
        if($retrieve){
            $after = DB::connection("mysql_game")->table("fib_userdata")->select(["id","score"])->whereNotNull("lastPlayed")->orderBy("score","desc")->limit(10)->get();
            $ranks = array();
            $names = array();
            $scores = array();
            $rank = 0;
            $last = 0;
            $count = 0;
            foreach($after as $single){
                $count ++;
                if($single->score == $last){

                }else{
                    $rank = $count;
                    $last = $single->score;
                }
                array_push($names,$single->username = UserCenterController::GetUserNickname($single->id));
                array_push($scores,$single->score);
                array_push($ranks,$rank);
            }
            return array("names"=>$names,"ranks"=>$ranks,"scores"=>$scores);
        } else {
            return array("count"=>$count+1,"score"=>$user->score,"expired"=>date('Y-m-d H:i:s',strtotime("+1 week",strtotime($user->lastPlayed))));
        }

    }

    function updateScore($id,$input){
        $rank = $this->getRank($id,false);

        $array["playerBefore"] = null;
        $array["playerAfter"] = null;
        if($rank["score"] / 2 <= $input || $input == 0){
            if($input>$rank["score"]){
                DB::connection("mysql_game")->table("fib_userdata")->where(["id"=>$id])->update(["score"=>$input,"lastPlayed"=> date('Y-m-d H:i:s'),"ip"=>$_SERVER["REMOTE_ADDR"]]);
                $rank = $this->getRank($id,false);
            }else if($rank["score"] / 2 <= $input){
                DB::connection("mysql_game")->table("fib_userdata")->where(["id"=>$id])->update(["lastPlayed"=> date('Y-m-d H:i:s'),"ip"=>$_SERVER["REMOTE_ADDR"]]);
            }
            $array["bestScore"] = $rank["score"];
            $array["bestRank"] = $rank["count"];
            if($input != 0){
                $array["nowRank"] = $array["bestRank"];
            }else{
                $count = DB::connection("mysql_game")->table("fib_userdata")->where("score",">",$input)->get();
                $count = count($count);
                $array["nowRank"] = $count + 1;

            }

            $before = DB::connection("mysql_game")->table("fib_userdata")->where("score",">",$rank["score"])->orderBy("score","asc")->first();
            $after = DB::connection("mysql_game")->table("fib_userdata")->where("score","<",$rank["score"])->orderBy("score","desc")->first();
            if(!is_null($before)){
                $array["playerBefore"]["username"] = UserCenterController::GetUserNickname($before->id);
                $array["playerBefore"]["score"] = $before->score;
            }else{
                $array["playerBefore"]["username"] = "";
                $array["playerBefore"]["score"] = "";
            }
            if(!is_null($after)){
                $array["playerAfter"]["username"] = UserCenterController::GetUserNickname($after->id);
                $array["playerAfter"]["score"] = $after->score;
            }else{
                $array["playerAfter"] = [];
            }
            $array["expired"] = $rank["expired"];
        } else {
            $array["bestScore"] = $rank["score"];
            $array["bestRank"] = $rank["count"];
            $array["expired"] = $rank["expired"];
            $count = DB::connection("mysql_game")->table("fib_userdata")->where("score",">",$input)->get();
            $count = count($count);
            $array["nowRank"] = $count + 1;

        }
        return $array;
    }
}
