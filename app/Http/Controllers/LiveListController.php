<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Response;

class LiveListController extends Controller
{
    function getLiveList(){
        return Response::json(DB::connection("mysql_live")->table("activity")->orderby("start","desc")->get());
    }
}
