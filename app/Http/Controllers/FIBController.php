<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Response;
use Cookie;

class FIBController extends Controller
{
    private $table = "fib_userdata";

    function request2048Handler(Request $request, $type){
        $this->table = "2048_userdata";
        return $this->requestHandler($request,$type);
    }
    
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
            case "purchase":
                if($request->isMethod("get")){
                    $info = $this->getPack($id);
                }else if($request->isMethod("post") && $request->has("pack")){
                    $info = $this->getPack($id,$request->input("pack"));
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
        $user = DB::connection('mysql_game')->table($this->table)->where('id', $id)->first();
        if (is_null($user))
            DB::connection('mysql_game')->table($this->table)->insert(['id' => $id]);
        return $id;
    }

    function checkScoreExpiration(){
        if($this->table!="fib_userdata")
            return;
        DB::connection("mysql_game")->table($this->table)->where("lastPlayed","<",date('Y-m-d H:i:s',strtotime("-1 week")))->update(["score"=>0]);
    }

    function getRank($id,$retrieve = true){
        $user = DB::connection("mysql_game")->table($this->table)->where(["id"=>$id])->first();
        $count = DB::connection("mysql_game")->table($this->table)->where("score",">",$user->score)->get();
        $count = count($count);
        if($retrieve){
            $after = DB::connection("mysql_game")->table($this->table)->select(["id","score"])->whereNotNull("lastPlayed")->orderBy("score","desc")->limit(10)->get();
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
                DB::connection("mysql_game")->table($this->table)->where(["id"=>$id])->update(["score"=>$input,"lastPlayed"=> date('Y-m-d H:i:s'),"ip"=>$_SERVER["REMOTE_ADDR"]]);
                $rank = $this->getRank($id,false);
            }else if($rank["score"] / 2 <= $input){
                DB::connection("mysql_game")->table($this->table)->where(["id"=>$id])->update(["lastPlayed"=> date('Y-m-d H:i:s'),"ip"=>$_SERVER["REMOTE_ADDR"]]);
            }
            $array["bestScore"] = $rank["score"];
            $array["bestRank"] = $rank["count"];
            if($input != 0){
                $array["nowRank"] = $array["bestRank"];
            }else{
                $count = DB::connection("mysql_game")->table($this->table)->where("score",">",$input)->get();
                $count = count($count);
                $array["nowRank"] = $count + 1;

            }

            $before = DB::connection("mysql_game")->table($this->table)->where("score",">",$rank["score"])->orderBy("score","asc")->first();
            $after = DB::connection("mysql_game")->table($this->table)->where("score","<",$rank["score"])->orderBy("score","desc")->first();
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
            $count = DB::connection("mysql_game")->table($this->table)->where("score",">",$input)->get();
            $count = count($count);
            $array["nowRank"] = $count + 1;

        }
        return $array;
    }

    function getPack($id,$used = null){
        if($this->table != "fib_userdata")
            abort(403);
        $data = DB::connection("mysql_game")->table($this->table)->select("doublePack","recoverPack")->where(["id"=>$id])->first();
        $info = array();
        if(!is_null($used)){
            switch($used){
                case "double":
                    if($data->doublePack > 0){
                        DB::connection("mysql_game")->table($this->table)->where(["id"=>$id])->update(["doublePack"=>$data->doublePack - 1]);
                        $info["process"] = true;
                    }else{
                        $info["process"] = false;
                    }
                    break;
                case "recover":
                    if($data->recoverPack > 0){
                        DB::connection("mysql_game")->table($this->table)->where(["id"=>$id])->update(["recoverPack"=>$data->recoverPack - 1]);
                        $info["process"] = true;
                    }else{
                        $info["process"] = false;
                    }
                    break;
            }
            $data = DB::connection("mysql_game")->table($this->table)->select("doublePack","recoverPack")->where(["id"=>$id])->first();
        }
        $info["recoverPack"] = $data->recoverPack;
        $info["doublePack"] = $data->doublePack;
        return $info;
    }

    function purchaseHandler($id,$product){
        $data = DB::connection("mysql_game")->table($this->table)->select("doublePack","recoverPack")->where(["id"=>$id])->first();
        $doublePack = $data->doublePack;
        $recoverPack = $data->recoverPack;
        switch($product){
            case 1011:
                $recoverPack += 2;
                break;
            case 1012:
                $recoverPack += 5;
                break;
            case 1013:
                $doublePack += 2;
                break;
            case 1014:
                $doublePack += 5;
                break;
        }
        DB::connection("mysql_game")->table($this->table)->where(["id"=>$id])->update(["recoverPack"=>$recoverPack,"doublePack"=>$doublePack]);
    }


}
