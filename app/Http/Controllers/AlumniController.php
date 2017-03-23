<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\DB;

class AlumniController extends Controller
{
    //
    function auth($step){
    	if(is_numeric($step)==true){
    		$user_id = DB::table("user_auth")->pluck("id")->first();
    		if(is_null($user_id))
    			return "Database is empty!";
    		return "Found the first one:".$user_id;
    	}
    	else
    		abort(403,"Check your input.");
	}
}
