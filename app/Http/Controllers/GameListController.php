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

    function getList(Request $request){
        $id = UserCenterController::GetUserId(Cookie::get("token"));
        $game_list = DB::connection("mysql_game")->table("_list")->select("id","name","description","icon","url")->get();
        $json_mes['code'] = 200;
        $json_mes['info'] = $game_list;

        return Response::json($json_mes, 200);
    }

    function purchaseManager($userId,$productId,$transactionId,$env="sandbox"){
        if($productId<1000)
            return;
        if(count(DB::connection("mysql_game")->table("_purchase")->where(["transaction_id"=>$transactionId])->get()) == 0){
            DB::connection("mysql_game")->table("_purchase")->insert(["user_id"=>$userId,"product_id"=>$productId,"transaction_id"=>$transactionId,"environment"=>$env]);
            $gameId = $productId / 10 % 10;
            switch($gameId){
                case 1:
                    $fib = new FIBController();
                    $fib->purchaseHandler($userId,$productId);
                    break;
                default:
                    break;
            }
        }
    }

}
