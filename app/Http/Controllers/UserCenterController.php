<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        include "conn.php";
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_users");
        mysqli_query($con,"set character set 'utf8'");
        $result = mysqli_query($con,"SELECT * FROM user_list WHERE id = $id");
        if(mysqli_num_rows($result)==1)
            return true;
        else
            return false;
    }

    function AddUser($id)//添加论坛用户到user表中
    {
        include "conn.php";
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_users");
        mysqli_query($con,"set character set 'utf8'");
        $result = mysqli_query($con,"INSERT INTO `user_list` (`id`) VALUES ($id)");
    }

    function CheckIfTokenExists($id)//检查是否存在Token
    {
        include "conn.php";
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_users");
        mysqli_query($con,"set character set 'utf8'");
        $result = mysqli_query($con,"SELECT * FROM user_list WHERE id = $id");
        if(mysqli_num_rows($result)==1)
        {
            $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
            if($row['token']==null)
                return false;
            else
                return $row['token'];
        }
    }

    function GenerateToken($id)//生成Token
    {
        include "conn.php";
        do
        {
            $str1=(string)microtime(true);
            $str2=(string)$id;
            $final=$str1."顾平德穿女装".$str2;
            $hash=hash("sha512",$final);
            $token = substr($hash,(strlen($hash)-64));
            $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_users");
            mysqli_query($con,"set character set 'utf8'");
            $result = @mysqli_query($con,'SELECT * FROM user_list WHERE token = "'.$token.'"');
        }while(@mysqli_num_rows($result)>0);
        unset($result);
        $result = mysqli_query($con,'UPDATE `user_list` SET `token`="'.$token.'" WHERE `id`='.$id);
        return $token;
    }

    function GeneratePassword($id)//生成密码
    {
        include "conn.php";
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_users");
        mysqli_query($con,"set character set 'utf8'");
        $result = mysqli_query($con,"SELECT * FROM user_list WHERE id = $id");
        if(@mysqli_num_rows($result)==1)
        {

            $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
            if($row['asso_password']!="")
                return $row['asso_password'];
            else
            {
                $str1=(string)microtime(true);
                $final=$str1."顾平德穿女装";
                $hash=hash("sha512",$final);
                $password = substr($hash,(strlen($hash)-16));
                unset($result);
                $result = mysqli_query($con,'UPDATE `user_list` SET `asso_password`="'.$password.'" WHERE `id`='.$id);
                return $password;
            }
        }



    }

    function GetUserIdByToken($token)//根据token获取用户id
    {
        include "conn.php";
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_users");
        mysqli_query($con,"set character set 'utf8'");
        $result = mysqli_query($con,'SELECT * FROM user_list WHERE token = "'.$token.'"');
        if(@mysqli_num_rows($result)==1)
        {
            $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
            return $row['id'];
        }
        else
        {
            die(json_encode(array("status"=>"error","message"=>"Invaid Token.")));
        }
    }

    function GetShadowsocksStatusById($id)//获取用户ss信息
    {
        include "conn.php";
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_users");
        mysqli_query($con,"set character set 'utf8'");
        $result = mysqli_query($con,"SELECT * FROM user_list WHERE id = $id");
        if(mysqli_num_rows($result)==1)
        {
            $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
            return $row['ss_account'];
        }
        else
        {
            return false;
        }
    }

    function GetShareStatusById($id)//获取用户share信息
    {
        include "conn.php";
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_users");
        mysqli_query($con,"set character set 'utf8'");
        $result = mysqli_query($con,"SELECT * FROM user_list WHERE id = $id");
        if(mysqli_num_rows($result)==1)
        {
            $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
            return $row['share_account'];
        }
        else
        {
            return false;
        }
    }

    function GetPersonalAuthStatusById($id)//根据id获取用户实名认证信息
    {
        include "conn.php";
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_users");
        mysqli_query($con,"set character set 'utf8'");
        $result = mysqli_query($con,"SELECT * FROM user_list WHERE id = $id");
        if(mysqli_num_rows($result)==1)
        {
            $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
            return $row['auth_info'];
        }
        else
        {
            return false;
        }
    }

    function GetUsernameById($id)//根据id获取用户名
    {
        include "conn.php";
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_forum");
        mysqli_query($con,"set character set 'utf8'");
        $result = mysqli_query($con,"SELECT * FROM nfls_users WHERE id = $id");
        if(mysqli_num_rows($result)==1)
        {
            $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
            return $row['username'];
        }
        else
        {
            return false;
        }
    }

    function GetAvatarById($id)//根据id获取头像
    {
        $headers = array('content-type:application/vnd.api+json',);
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, "https://forum.nfls.io/api/users/$id");
        //curl_setopt ($ch, CURLOPT_POST, 1);
        //$post_data = '{"identification":"'.$user.'","password":"'.$pass.'"}';
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        $detail=(array)json_decode($file_contents,true);
        //return $file_contents;
        //return $file_contents;
        return $detail['data']['attributes']['avatarUrl'];
    }

    function GetPersonalGeneralInfoById($id)//根据id获取综合信息
    {
        include "conn.php";
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_forum");
        mysqli_query($con,"set character set 'utf8'");
        $result = mysqli_query($con,"SELECT * FROM nfls_users WHERE id = $id");
        if(mysqli_num_rows($result)==1)
        {
            $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
            $info=array();
            $info['id']=$row['id'];
            $info['username']=$row['username'];
            $info['email']=$row['email'];
            $info['is_activated']=$row['is_activated'];
            $info['bio']=$row['bio'];
            $info['avatar_path']=$row['avatar_path'];
            $info['join_time']=$row['join_time'];
            return $info;
        }
        else
        {
            return false;
        }
    }

    function GetPersonalForumInfoById($id)//根据id获取论坛信息
    {
        include "conn.php";
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_forum");
        mysqli_query($con,"set character set 'utf8'");
        $result = mysqli_query($con,"SELECT * FROM nfls_users WHERE id = $id");
        if(mysqli_num_rows($result)==1)
        {
            $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
            $info=array();
            $info['id']=$row['id'];
            $info['username']=$row['username'];
            $info['last_seen_time']=$row['last_seen_time'];
            $info['notifications_read_time']=$row['notifications_read_time'];
            $info['discussions_count']=$row['discussions_count'];
            $info['comments_count']=$row['comments_count'];
            return $info;
        }
        else
        {
            return false;
        }
    }

    function GetUserWikiInfoByWikiId($id)//根据wiki_id获取wiki信息
    {
        include "conn.php";
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_wiki");
        mysqli_query($con,"set character set 'utf8'");
        $result = mysqli_query($con,"SELECT * FROM wiki_user WHERE user_id = $id");
        if(mysqli_num_rows($result)==1)
        {
            $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
            $info=array();
            $info['user_id']=$row['user_id'];
            $info['user_name']=$row['user_name'];
            $info['user_real_name']=$row['user_real_name'];
            $info['user_touched']=$row['user_touched'];
            $info['user_registration']=$row['user_registration'];
            $info['user_editcount']=$row['user_editcount'];
            return $info;
        }
        else
        {
            return false;
        }
    }

    function GetUserShareInfoByShareId($id)//根据shareid获取share信息
    {
        include "conn.php";
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_share");
        mysqli_query($con,"set character set 'utf8'");
        $result = mysqli_query($con,"SELECT * FROM users WHERE id = $id");
        if(mysqli_num_rows($result)==1)
        {
            $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
            $info=array();
            $info['user_id']=$row['id'];
            $info['user_name']=$row['username'];
            $info['user_touched']=$row['last_login'];
            $info['user_registration']=$row['added'];
            $info['user_ip']=$row['ip'];
            $info['user_uploaded']=$row['uploaded'];
            $info['user_downloaded']=$row['downloaded'];
            return $info;
        }
        else
        {
            return false;
        }
    }
    function GetUserAssiciatedIdById($id,$service)
    {
        //echo $id;
        include "conn.php";
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_users");
        mysqli_query($con,"set character set 'utf8'");
        $result = mysqli_query($con,"SELECT * FROM user_list WHERE id = $id");
        if(mysqli_num_rows($result)==1)
        {
            $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
            switch($service)
            {
                case "shadowsocks":
                    $id=$row['ss_account'];
                    break;
                case "share":
                    $id=$row['share_account'];
                    break;
                case "wiki":
                    $id=$row['wiki_account'];
                    break;
            }
            return $id;
        }
        else
        {
            return false;
        }
    }

    function CreateWikiAccountById($id)//注册wiki账户
    {
        include "conn.php";
        if(GetUserAssiciatedIdById($id,"wiki")!=-1)
            die(json_encode(array("status"=>"error")));
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
        $post_data = "username=$wiki_bot&password=$bot_pass&logintoken=$wiki_token&format=json&loginreturnurl=$url";
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
        $con = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_wiki");
        mysqli_query($con,"set character set 'utf8'");
        $username = ucfirst($username);
        $result = mysqli_query($con,"SELECT * FROM wiki_user WHERE user_name = '".$username."'");
        if(mysqli_num_rows($result)==1)
        {
            $row=mysqli_fetch_array($result,MYSQLI_ASSOC);
            $con1 = mysqli_connect($sql_add,$sql_user,$sql_pass,"nfls_users");
            mysqli_query($con1,"set character set 'utf8'");
            mysqli_query($con1,'UPDATE `user_list` SET `wiki_account`="'.$row['user_id'].'" WHERE `id`='.$id);
        }
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
