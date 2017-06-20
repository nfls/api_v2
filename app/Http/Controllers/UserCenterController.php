<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\PaginationServiceProvider;
use Illuminate\Support\Facades\DB;

class UserCenterController extends Controller
{
    public static function GetUserId($token){
    	$user = DB::connection("mysql_user")->table("user_list")->where("token", $token)->first();
    	if(is_null($user))
    		return -1;
    	return $user->id;
    }

    public static function GetUserNickname($id){
    	$user = DB::connection("mysql_forum")->table("nfls_users")->where("id", $id)->first();
    	return $user->username;
    }

    public static function GetUserEmail($id){
    	$user = DB::connection("mysql_forum")->table("nfls_users")->where("id", $id)->first();
    	return $user->email;
    }

    public static function checkAdmin($id){
        return true;
    }

    function UserLogin($username,$password)
    {
        $headers = array('content-type:application/vnd.api+json',);
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, "https://forum.nfls.io/api/token");
        curl_setopt ($ch, CURLOPT_POST, 1);
        $post_data = '{"identification":"'.$username.'","password":"'.$password.'"}';
        if($post_data != ''){curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);}
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        $detail=(array)json_decode($file_contents,true);
        //echo $file_contents;
        //return $file_contents;
        //preg_match_all("/Set\-cookie:([^\r\n]*)/i",$file_contents,$str);
        //$opt_cookie=array();
        //$opt_cookie[$i]=urldecode(substr($str[1][$i],1));
        //curl_close($ch);
        unset($ch);
        if(isset($detail['token']))
            return LoginProcess($detail['userId']);
        else
            return false;

    }

    function ForumLogin($username,$password)
    {
        $headers = array('content-type:application/vnd.api+json',);
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, "https://forum.nfls.io/login");
        curl_setopt ($ch, CURLOPT_POST, 1);
        $post_data = '{"identification":"'.$username.'","password":"'.$password.'"}';
        if($post_data != ''){curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);}
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        $detail=(array)json_decode($file_contents,true);
        //echo $file_contents;
        preg_match_all("/Set\-cookie:([^\r\n]*)/i",$file_contents,$str);
        //echo json_encode($str);
        $opt_cookie=array();
        $opt_cookie[0]=substr($str[1][0],1);
        $opt_cookie[1]=substr($str[1][1],1);
        //echo $str[0][0];
        //curl_close($ch);
        unset($ch);
        return $opt_cookie;

    }

    function LoginProcess($id)
    {
        if(!CheckIfUserExists($id))
            AddUser($id);
        //GenerateToken($id);
        $token = CheckIfTokenExists($id);
        if(!$token)
            $token = GenerateToken($id);
        return($token);
    }

    function RecoverPassword($email)
    {
        $headers = array('content-type:application/vnd.api+json',);
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, "https://forum.nfls.io/api/forgot");
        curl_setopt ($ch, CURLOPT_POST, 1);
        $post_data = '{"email":"'.$email.'"}';
        if($post_data != ''){curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);}
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        $detail=(array)json_decode($file_contents,true);
        if($file_contents==null)
            return array("status"=>"success");
        else
            return array("status"=>"error");
        unset($ch);
    }

    function CheckIfUserExists($id) //检查论坛用户是否存在于user表中
    {
        $user = DB::connection("mysql_user")->table("user_list")->where("id", $id)->first();
        if(is_null($user))
            return false;
        return true;
    }

    function AddUser($id)//添加论坛用户到user表中
    {
        DB:conncetion("mysql_user")->table("user_list")->insert(["id"=>$id]);
    }

    function CheckIfTokenExists($id)//检查是否存在Token
    {
        $user = DB::connection("mysql_user")->table("user_list")->where("id", $id)->first();
        if(@is_null($user->token))
            return false;
        return $user->token;
    }

    function GenerateToken($id)//生成Token
    {
        do
        {
            $str1=(string)microtime(true);
            $str2=(string)$id;
            $final=$str1."顾平德穿女装".$str2;
            $hash=hash("sha512",$final);
            $token = substr($hash,(strlen($hash)-64));
            $db = DB::connection("mysql_user")->table("user_list")->where("token",$token)->first();
        }while(@!is_null($db->token));
        DB::connection("mysql_user")->table("user_list")->where(["id"=>$id])->update(["token"=>$token]);
        return $token;
    }

    function GetAssociatePassword($id)//生成密码
    {
        $user = DB::connection("mysql_user")->table("user_list")->where(["id"=>$id])->first();
        if(@is_null($user->asso_password)){
            $str1=(string)microtime(true);
            $final=$str1."顾平德穿女装";
            $hash=hash("sha512",$final);
            $password = substr($hash,(strlen($hash)-16));
            DB::connection("mysql_user")->table("user_list")->where(["id"=>$id])->update(["asso_password"=>$password]);
            return $password;
        }
        else {
            return $user->asso_password;
        }
    }

    function GetShareStatusById($id)//获取用户share信息
    {
        $user = DB::connection("mysql_user")->table("user_list")->where("id", $id)->first();
        return $user->share_account;
    }


    function GetUsernameById($id)//根据id获取用户名
    {
        $user = DB::connection("mysql_user")->table("user_list")->where("id", $id)->first();
        return $user->username;
    }

    function GetAvatarById($id)//根据id获取头像
    {
        $headers = array('content-type:application/vnd.api+json',);
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, "https://forum.nfls.io/api/users/$id");
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        $detail=(array)json_decode($file_contents,true);
        return $detail['data']['attributes']['avatarUrl'];
    }

    function GetPersonalGeneralInfoById($id)//根据id获取综合信息
    {
        $info = DB::connection("mysql_forum")->table("nfls_forum")->where(["id"=>$id])->first();
        $info=array();
        $info['id']=$info->id;
        $info['username']=$info->username;
        $info['email']=$info->email;
        $info['is_activated']=$info->is_activated;
        $info['bio']=$info->bio;
        $info['avatar_path']=$info->avatar_path;
        $info['join_time']=$info->join_time;
        return $info;
    }

    function GetPersonalForumInfoById($id)//根据id获取论坛信息
    {
        $user = DB::connection("mysql_forum")->table("nfls_users")->where(["id"=>$id])->first();
        $info=array();
        $info['id']=$user->id;
        $info['username']=$user->username;
        $info['last_seen_time']=$user->last_seen_time;
        $info['notifications_read_time']=$user->notifications_read_time;
        $info['discussions_count']=$user->discussions_count;
        $info['comments_count']=$user->comments_count;
        return $info;
    }

    function GetUserWikiInfoByWikiId($id)//根据wiki_id获取wiki信息
    {
        $user = DB::connection("mysql_wiki")->table("wiki_user")->where(["user_id"=>$id])->first();
        $info=array();
        $info['user_id']=$user->user_id;
        $info['user_name']=$user->user_name;
        $info['user_real_name']=$user->user_real_name;
        $info['user_touched']=$user->user_touched;
        $info['user_registration']=$user->user_registration;
        $info['user_editcount']=$user->user_editcount;
        return $info;
    }

    function GetUserShareInfoByShareId($id)//根据shareid获取share信息
    {
        $user = DB::connection("mysql_share")->table("users")->where(["id"=>$id])->first();
        $info=array();
        $info['user_id']=$user->id;
        $info['user_name']=$user->username;
        $info['user_touched']=$user->last_login;
        $info['user_registration']=$user->added;
        $info['user_ip']=$user->ip;
        $info['user_uploaded']=$user->uploaded;
        $info['user_downloaded']=$user->downloaded;
        return $info;
    }
    function GetUserAssiciatedIdById($id,$service)
    {
        $user = DB::connection("mysql_user")->table("user_list")->where("id", $id)->first();
        switch($service) {
            case "share":
                return $user->share_account;
            case "wiki":
                return $user->wiki_accoount;
            default:
                abort(403);
        }
    }

    function CreateWikiAccountById($id)//注册wiki账户
    {
        if(GetUserAssiciatedIdById($id,"wiki")!=-1)
            abort(403);
        $cookie = tempnam('./','cookie');
        $cookie2 = tempnam('./','cookie2');
        $headers = array('Content-Type: application/x-www-form-urlencoded','Cache-Control: no-cache','Api-User-Agent: Example/1.0',);
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, "https://wiki.nfls.io/api.php?action=query&type=login&meta=tokens&format=json");
        curl_setopt ($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch,CURLOPT_COOKIEJAR,$cookie);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        $detail=(array)json_decode($file_contents,true);
        $wiki_token=urlencode($detail['query']['tokens']['logintoken']);
        unset($ch);

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, "https://wiki.nfls.io/api.php?action=clientlogin");
        curl_setopt ($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        $url=urlencode("https://login.nfls.io");
        curl_setopt($ch,CURLOPT_COOKIEFILE,$cookie);
        curl_setopt($ch,CURLOPT_COOKIEJAR,$cookie2);
        $post_data = "username="+env("WIKI_BOT")+"&password="+env("BOT_PASS")+"&logintoken=$wiki_token&format=json&loginreturnurl=$url";
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        unset($ch);

        $ch = curl_init();

        curl_setopt ($ch, CURLOPT_URL, "https://wiki.nfls.io/api.php?action=query&type=createaccount&meta=tokens&format=json");
        curl_setopt ($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        //curl_setopt($ch,CURLOPT_COOKIEFILE,$cookie);
        curl_setopt($ch,CURLOPT_COOKIEFILE,$cookie2);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        $detail=(array)json_decode($file_contents,true);
        unset($ch);

        $wiki_token=urlencode($detail['query']['tokens']['createaccounttoken']);
        $info=GetPersonalGeneralInfoById($id);
        $email=urlencode($info['email']);
        $username=$info['username'];
        $password=GeneratePassword($id);

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, "https://wiki.nfls.io/api.php?action=createaccount");
        curl_setopt ($ch, CURLOPT_POST, 1);
        $post_data = "username=$username&password=$password&retype=$password&email=$email&createreturnurl=$url&createtoken=$wiki_token&format=json";
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch,CURLOPT_COOKIEFILE,$cookie2);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        $detail=(array)json_decode($file_contents,true);
        $user = DB::connection("mysql_wiki")->table("wiki_user")->where(["user_name"=>$username])->first();
        DB::connection("mysql_user")->table("user_list")->where(["id"=>$id])->update(["wiki_account"=>$user->user_id]);
        return 0;
    }

    function LoginWikiAccountById($id)//登录wiki账户
    {
        include "conn.php";
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_users");
        mysqli_query($con,"set character set 'utf8'");
        $result = mysqli_query($con,"SELECT * FROM user_list WHERE id=$id");
        $row=mysqli_fetch_array($result,MYSQLI_ASSOC);
        $username=urlencode(GetUsernameById($id));
        $password=$row['asso_password'];

        $cookie = tempnam('./','cookie');
        $cookie2 = tempnam('./','cookie2');
        $headers = array('Content-Type: application/x-www-form-urlencoded','Cache-Control: no-cache','Api-User-Agent: Example/1.0',);
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, "https://wiki.nfls.io/api.php?action=query&type=login&meta=tokens&format=json");
        curl_setopt ($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch,CURLOPT_COOKIEJAR,$cookie);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        $detail=(array)json_decode($file_contents,true);
        $wiki_token=urlencode($detail['query']['tokens']['logintoken']);
        unset($ch);

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, "https://wiki.nfls.io/api.php?action=clientlogin");
        curl_setopt ($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch,CURLOPT_COOKIEFILE,$cookie);
        curl_setopt($ch,CURLOPT_COOKIEJAR,$cookie2);
        $url=urlencode("https://login.nfls.io");
        $post_data = "username=$username&password=$password&logintoken=$wiki_token&format=json&loginreturnurl=$url&rememberMe=true";
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $file_contents = curl_exec($ch);
        //echo $file_contents ;
        preg_match_all("/Set\-cookie:([^\r\n]*)/i",$file_contents,$str);
        $opt_cookie=array();
        for($i=0;$i<4;$i++)
        {
            $opt_cookie[$i]=urldecode(substr($str[1][$i],1));
        }
        //echo json_encode($opt_cookie);
        curl_close($ch);
        unset($ch);
        return $opt_cookie;
    }

    function LoginShareAccountById($id)//登录Share账户
    {
        include "conn.php";
        $id=GetUserAssiciatedIdById($id,"share");
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_share");
        mysqli_query($con,"set character set 'utf8'");
        $result = mysqli_query($con,"SELECT * FROM users WHERE id = $id");
        if(mysqli_num_rows($result)==1)
        {
            $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
            $info=array();
            $info['c_secure_uid']=urlencode(base64_encode($row['id']));
            $info['c_secure_pass']=urlencode(md5($row["passhash"]));
            $info['c_secure_ssl']=urlencode(base64_encode("yeah"));
            $info['c_secure_tracker_ssl']=urlencode(base64_encode("yeah"));
            $info['c_secure_login']=urlencode(base64_encode("nope"));
            return $info;
        }
        else
        {
            return false;
        }
    }

    function CreateShareAccountById($id)//注册wiki账户
    {
        if(GetUserAssiciatedIdById($id,"share")!=-1)
            die(json_encode(array("status"=>"error")));
        $secret=mksecret();
        $password=GeneratePassword($id);
        $info=GetPersonalGeneralInfoById($id);
        $email=$info['email'];
        $username=$info['username'];
        $wantpasshash = md5($secret . $password . $secret);
        $time=date('Y-m-d h:i:s',time());
        include "conn.php";
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_share");
        mysqli_query($con,"set character set 'utf8'");
        mysqli_query($con,"SET sql_mode = 'ALLOW_INVALID_DATES'");
        mysqli_query($con,"INSERT INTO users (username, passhash, secret, email, added, last_login, status) values('$username','$wantpasshash','$secret','$email','$time','$time', 'confirmed')");
        $result = mysqli_query($con,"SELECT * FROM users WHERE username = '".$username."'");
        if(mysqli_num_rows($result)==1)
        {
            $row=mysqli_fetch_array($result,MYSQLI_ASSOC);
            $con1 = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_users");
            mysqli_query($con1,"set character set 'utf8'");
            mysqli_query($con1,'UPDATE `user_list` SET `share_account`="'.$row['id'].'" WHERE `id`='.$id);
        }
        return 0;

    }
    function mksecret($len = 20) {//share secret制作
        $ret = "";
        for ($i = 0; $i < $len; $i++)
            $ret .= chr(mt_rand(100, 120));
        return $ret;
    }

}
