<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Response;
class AlbumController extends Controller
{
    function getPhotoList($id)
    {
        $accessKey = env("QINIU_AK");
        $secretKey = env("QINIU_SK");
        $auth = new Auth($accessKey, $secretKey);
        $bucketMgr = new BucketManager($auth);
        $fileList = $bucketMgr->listFiles("nfls-china", "Gallery/" . $id)[0];
        $imageList = array();
        $i = 0;
        foreach ($fileList as $file) {
            $imageList[$i] = "https://global.nfls.io/" . $file['key'];
            $i++;
        }
        echo json_encode($imageList);
    }

    function getAlbumList(){

    }

    function getAlbumInfo($id){

    }

    function updateAnAlbum(Request $request)
    {
        //if(!UserCenterController::checkAdmin(UserCenterController::GetUserId($request->cookie('token'))))
        //    abort(403);
        if ($request->only('method', 'name', 'date', 'author', 'license', 'intro')){
            if($request->input('method')=='new')
                DB::connection("mysql_alumni")->table("album")->insert([
                    "name"=>$request->input('name'),
                    "date"=>$request->input('date'),
                    'author'=>$request->input('author'),
                    'license'=>$request->input('license'),
                    'intro'=>$request->input('intro')]);
        }else if($request->only('method', 'name', 'date', 'author', 'license', 'intro', 'id')){
            if($request->input('method'=='update')){
                DB::connection("mysql_alumni")->table("album")->where('id',$request->input('id'))->update([
                    "name"=>$request->input('name'),
                    "date"=>$request->input('date'),
                    'author'=>$request->input('author'),
                    'license'=>$request->input('license'),
                    'intro'=>$request->input('intro')]);
            }
        }
        else{
            return Response::json(array("status"=>"failed"));
        }
        return Response::json(array("status"=>"success"));
    }
}