<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Response;

class LiveListController extends Controller
{
    function getLiveList(){
        return Respones::json(DB::connection("mysql_live")->table("activity")->get());
    }
}
