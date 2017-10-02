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

class GameListController extends controller
{
    function test(){
        $message = "We're Mr Tunnel's Lonely Hearts Computing Club, We hope you will enjoy the bugs";
        return Response::json(array("code"=>200,"info"=>$message));
    }

}
