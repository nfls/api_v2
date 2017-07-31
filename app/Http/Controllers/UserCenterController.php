<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\PaginationServiceProvider;
use Illuminate\Support\Facades\DB;
use Response;
use Cookie;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\DeviceParserAbstract;


class UserCenterController extends Controller
{
    public static function GetUserId($token){
    	$user = DB::connection("mysql_user")->table("user_list")->where("token", $token)->first();
    	if(is_null($user))
    		abort(403);
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

    public static function isUserExist($id){
        $user = DB::connection("mysql_forum")->table("nfls_users")->where("id", $id)->first();
        if(@is_null($user->username))
            return false;
        else
            return true;
    }

    function requestHandler(Request $request, $type){
        switch($type){
            case "login":
                if($request->only(['username','password','session','captcha'] && $request->isMethod("post")))
                    $info = $this->UserLogin($request->input("username"), $request->input("password"),$request->input("session"),$request->input("captcha"));
                break;
            case "recover":
                if($request->only(['email','session','captcha']) && $request->isMethod("post"))
                    $info = $this->RecoverPassword($request->input("email"),$request->input("session"),$request->input("captcha"));
                break;
            case "register":
                if($request->only(['username','password','email',"session","captcha"]) && $request->isMethod("post"))
                    $info = $this->UserRegister($request->input("email"),$request->input("password"),$request->input("username"),$request->input("session"),$request->input("captcha"));
                break;
            case "username":
                if($request->isMethod("get"))
                    $info = $this->GetUsernameById(self::GetUserId(Cookie::get('token')));
                break;
            case "forumLogin":
                if($request->only(['username','password','token']) && $request->isMethod("post"))
                    $info = $this->ForumLogin($request->input("username"), $request->input("password"), $request->input("token"));
                break;
            case "wikiLogin":
                if($request->isMethod("get"))
                    $info = $this->LoginWikiAccountById(self::GetUserId(Cookie::get('token')));
                break;
            case "shareLogin":
                if($request->isMethod("get"))
                    $info = $this->LoginShareAccountById(self::GetUserId(Cookie::get('token')));
                break;
            case "wikiRegister":
                if($request->isMethod("get")) {
                    $this->CreateWikiAccountById(self::GetUserId(Cookie::get('token')));
                    $info['status'] = "succeed";
                }
                break;
            case "shareRegister":
                if($request->isMethod("get")) {
                    $this->CreateShareAccountById(self::GetUserId(Cookie::get('token')));
                    $info['status'] = "succeed";
                }
                break;
            case "avatar":
                if($request->isMethod("get"))
                    $info['url'] = $this->GetAvatarById($this->GetUserId(Cookie::get('token')));
                break;
            case "generalInfo":
                if($request->isMethod("get"))
                    $info = $this->GetPersonalGeneralInfoById(self::GetUserId(Cookie::get('token')));
                break;
            case "forumInfo":
                if($request->isMethod("get"))
                    $info = $this->GetPersonalForumInfoById(self::GetUserId(Cookie::get('token')));
                break;
            case "wikiInfo":
                if($request->isMethod("get"))
                    $info = $this->GetUserWikiInfoByWikiId($this->GetUserAssociatedIdById(self::GetUserId(Cookie::get('token')),"wiki"));
                break;
            case "shareInfo":
                if($request->isMethod("get"))
                    $info = $this->GetUserShareInfoByShareId($this->GetUserAssociatedIdById(self::GetUserId(Cookie::get('token')),"share"));
                break;
            case "systemMessage":
                if($request->isMethod("get"))
                    $info = $this->GetSystemNoticeById(self::GetUserId(Cookie::get('token')));
                break;
            case "registerCaptcha":
                if($request->isMethod("get"))
                    $info = $this->CreateCaptcha($_SERVER['REMOTE_ADDR'],"register");
                break;
            case "loginCaptcha":
                if($request->isMethod("get"))
                    $info = $this->CreateCaptcha($_SERVER['REMOTE_ADDR'],"login");
                break;
            case "recoverCaptcha":
                if($request->isMethod("get"))
                    $info = $this->CreateCaptcha($_SERVER['REMOTE_ADDR'],"recover");
                break;
            case "device":
                if($request->isMethod("get"))
                    $info = $this->getDevice();
                break;
            case "notice":
                if($request->isMethod("get"))
                    $info = $this->getNotice();
                break;
            default:
                break;
        }
        $json_mes = array();
        if(@is_null($info)||empty($info)){
            $json_mes['code'] = 403;
            $json_mes['status'] = "error";
        }
        else{
            $json_mes['code'] = 200;
            $json_mes['status'] = "succeed";
            $json_mes['info'] = $info;
        }
        return Response::json($json_mes);
    }

    function getDevice(){
        //echo $_SERVER['HTTP_USER_AGENT'];
        $dd = new DeviceDetector($_SERVER['HTTP_USER_AGENT']);
        $dd->parse();
        if ($dd->isBot()) {
            // handle bots,spiders,crawlers,...
            $botInfo = $dd->getBot();
        } else {
            $clientInfo = $dd->getClient(); // holds information about browser, feed reader, media player, ...
            $osInfo = $dd->getOs();
            $device = $dd->getDevice();
            $brand = $dd->getBrandName();
            $model = $dd->getModel();
            return array("info"=>$clientInfo,"os"=>$osInfo,"device"=>$device,"brand"=>$brand,"model"=>$model);
        }
    }

    function getNotice(){
        $allow = true;
        $message = "";
        $dd = new DeviceDetector($_SERVER['HTTP_USER_AGENT']);
        $dd->parse();
        if ($dd->isBot()) {
            $botInfo = $dd->getBot();
            //$message = "禁止非浏览器访问！";
        } else {
            $clientInfo = $dd->getClient(); // holds information about browser, feed reader, media player, ...
            if($clientInfo["type"]!="browser"){
                $allow = false;
                $message = "禁止非浏览器访问！";
            } else {
                $osInfo = $dd->getOs();
                $device = $dd->getDevice();
                $brand = $dd->getBrandName();
                $model = $dd->getModel();
                if($osInfo['name'] == "iOS"){
                    $allow = false;
                    $message = "由于iOS的WebKit与本站存在兼容性问题，导致无法正常登陆，请使用我们的客户端进行访问。";
                } else {
                    if($clientInfo['name']!="Chrome" && $clientInfo['name']!="Chrome Mobile"){
                        $message = "本站建议使用Chrome进行访问，不保证对于其他浏览器完全兼容。";
                    }
                }
            }

        }
        return array("allow"=>$allow,"message"=>$message);
    }
    function CreateCaptcha($ip,$operation){
        DB::connection("mysql_user")->table("user_session")->where("valid_before","<",date('Y-m-d h:i:s'))->delete();
        $phraseBuilder = new PhraseBuilder(10);
        $builder = new CaptchaBuilder(null, $phraseBuilder);
        $builder->buildAgainstOCR($width = 300, $height = 100, $font = null);
        //header('Content-type: image/jpeg');
        $phrase = $builder->getPhrase();
        $time = date('Y-m-d h:i:s', strtotime('+10 minutes'));
        $session = $this->random_str(16);
        DB::connection("mysql_user")->table("user_session")->insert(["phrase"=>$phrase,"ip"=>$ip,"valid_before"=>$time,"session"=>$session,"operation"=>$operation]);
        $image = 'data:image/jpeg;base64,' . base64_encode($builder->get($quality = 100));
        return array("captcha"=>$image,"session"=>$session);

    }

    function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }

    function UserLogin($username,$password,$session,$captcha)
    {
        $valid = DB::connection("mysql_user")->table("user_session")->where(["session" => $session, "operation" => "login", "phrase" => $captcha, "ip" => $_SERVER['REMOTE_ADDR']])->first();
        if(@is_null($valid->id)){
            DB::connection("mysql_user")->table("user_session")->where(["session" => $session])->delete();
            return array("status"=>"failure","message"=>"验证码无效或不正确");
        } else {
            DB::connection("mysql_user")->table("user_session")->where(["session" => $session, "operation" => "login", "phrase" => $captcha, "ip" => $_SERVER['REMOTE_ADDR']])->delete();
        }
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
        unset($ch);
        if(isset($detail['token']))
            return array("status"=>"success","token"=>$this->LoginProcess($detail['userId']));
        else
            return array("status"=>"failure","message"=>"用户名或密码不正确");
    }

    function UserRegister($email,$password,$username,$session,$captcha){
        /*
        if(preg_match("[A-Za-z0-9_]+",$username)!=$username)
            return [""]
        */
        $valid = DB::connection("mysql_user")->table("user_session")->where(["session" => $session, "operation" => "register", "phrase" => $captcha, "ip" => $_SERVER['REMOTE_ADDR']])->first();
        if(@is_null($valid->id)){
            DB::connection("mysql_user")->table("user_session")->where(["session" => $session])->delete();
            return array("status"=>"failure","message"=>"验证码无效或不正确");
        } else {
            DB::connection("mysql_user")->table("user_session")->where(["session" => $session, "operation" => "register", "phrase" => $captcha, "ip" => $_SERVER['REMOTE_ADDR']])->delete();
        }
        $headers = array('content-type:application/vnd.api+json');
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, "https://forum.nfls.io/api/users");
        curl_setopt ($ch, CURLOPT_POST, 1);
        $post_data = '{"data":{"attributes":{"username":"'.$username.'","email":"'.$email.'", "password":"'.$password.'"}}}';
        if($post_data != ''){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        $detail=(array)json_decode($file_contents,true);
        if(isset($detail['data']))
        {
            return array("status"=>"success");
        }
        if(isset($detail['errors']))
        {
            //die($file_contents);
            return array("status"=>"failure","code"=>$detail['errors'][0]['status'],"general"=>$detail['errors'][0]['code'],"message"=>$detail['errors'][0]['detail']);
        }
    }

    function ForumLogin($username,$password,$token)
    {
        self::GetUserId($token);
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
        if(!$this->CheckIfUserExists($id))
            $this->AddUser($id);
        $token = $this->CheckIfTokenExists($id);
        if(!$token)
            $token = $this->GenerateToken($id);
        return($token);
    }

    function RecoverPassword($email,$session,$captcha)
    {
        $valid = DB::connection("mysql_user")->table("user_session")->where(["session" => $session, "operation" => "recover", "phrase" => $captcha, "ip" => $_SERVER['REMOTE_ADDR']])->first();
        if(@is_null($valid->id)){
            DB::connection("mysql_user")->table("user_session")->where(["session" => $session])->delete();
            return array("status"=>"failure","message"=>"验证码无效或不正确");
        } else {
            DB::connection("mysql_user")->table("user_session")->where(["session" => $session, "operation" => "recover", "phrase" => $captcha, "ip" => $_SERVER['REMOTE_ADDR']])->delete();
        }
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
        if($file_contents==null)
            return array("status"=>"success");
        else
            return array("status"=>"failure","message"=>"未找到您的邮箱");
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
        DB::connection("mysql_user")->table("user_list")->insert(["id"=>$id]);
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
        $user = DB::connection("mysql_forum")->table("nfls_users")->where("id", $id)->first();
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
        $user = DB::connection("mysql_forum")->table("nfls_users")->where(["id"=>$id])->first();
        $info=array();
        $info['id']=$user->id;
        $info['username']=$user->username;
        $info['email']=$user->email;
        $info['is_activated']=$user->is_activated;
        $info['bio']=$user->bio;
        $info['avatar_path']=$user->avatar_path;
        $info['join_time']=$user->join_time;
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
        if($id==-1)
            return [];
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
        if($id==-1)
            return [];
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
    function GetUserAssociatedIdById($id,$service)
    {
        $user = DB::connection("mysql_user")->table("user_list")->where("id", $id)->first();
        switch($service) {
            case "share":
                return $user->share_account;
            case "wiki":
                return $user->wiki_account;
            default:
                abort(403);
        }
    }

    function CreateWikiAccountById($id)//注册wiki账户
    {
        if($this->GetUserAssociatedIdById($id,"wiki")!=-1)
            abort(403);
        $cookie = tempnam('/tmp/','cookie');
        $cookie2 = tempnam('/tmp/','cookie2');
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
        $post_data = "username=".env("WIKI_BOT")."&password=".env("BOT_PASS")."&logintoken=$wiki_token&format=json&loginreturnurl=$url";
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
        $info=$this->GetPersonalGeneralInfoById($id);
        $email=urlencode($info['email']);
        $username=$info['username'];
        $password=$this->GetAssociatePassword($id);

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
        $username = ucfirst(str_replace("_", " ", $username));
        $user = DB::connection("mysql_wiki")->table("wiki_user")->where(["user_name"=>$username])->first();
        DB::connection("mysql_user")->table("user_list")->where(["id"=>$id])->update(["wiki_account"=>$user->user_id]);
        return array("status"=>"success");
    }

    function LoginWikiAccountById($id)//登录wiki账户
    {
        $wiki_id=$this->GetUserAssociatedIdById($id,"wiki");
        if($wiki_id==-1){
            return [];
        }
        $username=urlencode(self::GetUsernameById($id));
        $password = $this->GetAssociatePassword($id);

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
        $id=$this->GetUserAssociatedIdById($id,"share");
        if($id==-1){
            return [];
        }
        $user = DB::connection("mysql_share")->table("users")->where(["id"=>$id])->first();
        $info=array();
        $info['c_secure_uid']=urlencode(base64_encode($user->id));
        $info['c_secure_pass']=urlencode(md5($user->passhash));
        $info['c_secure_ssl']=urlencode(base64_encode("yeah"));
        $info['c_secure_tracker_ssl']=urlencode(base64_encode("yeah"));
        $info['c_secure_login']=urlencode(base64_encode("nope"));
        return $info;
    }

    function CreateShareAccountById($id)//注册share账户
    {
        if($this->GetUserAssociatedIdById($id,"share")!=-1)
            abort(403);
        $secret=$this->mksecret();
        $password=$this->GetAssociatePassword($id);
        $info=$this->GetPersonalGeneralInfoById($id);
        $email=$info['email'];
        $username=$info['username'];
        $wantpasshash = md5($secret . $password . $secret);
        $time=date('Y-m-d h:i:s',time());
        DB::connection("mysql_share")->select("SET sql_mode = 'ALLOW_INVALID_DATES'");
        DB::connection("mysql_share")->table("users")->insert(["username"=>$username,"passhash"=>$wantpasshash,"secret"=>$secret,"email"=>$email,"added"=>$time,"last_login"=>$time,"status"=>"confirmed"]);
        $user = DB::connection("mysql_share")->table("users")->where(["username"=>$username])->first();
        DB::connection("mysql_user")->table("user_list")->where(["id"=>$id])->update(["share_account"=>$user->id]);
        return array("status"=>"success");
    }

    function GetNoticeType($type)//获取通知类型
    {
        switch($type)
        {
            case "1":
                return "测试";
                break;
            case "2":
                return "通知";
                break;
            case "3":
                return "公告";
                break;
            case "4":
                return "预告";
                break;
            default:
                return "";
                break;
        }
    }

    function GetSystemNoticeById($id)//获取主站通知或推送，并根据Token记录已读信息
    {
        DB::connection("mysql_user")->table("user_list")->where(["id"=>$id])->first();
        $messages = DB::connection("mysql_user")->table("system_message")->where(["receiver" => $id])->orWhere(["receiver" => -1])->get();
        $count = 0;
        foreach($messages as $message){
            $info[$count]['time'] = $message -> time;
            $info[$count]['title'] = $message -> title;
            $info[$count]['type'] = $this->GetNoticeType($message -> type);
            $info[$count]['detail'] = $message -> detail;
            $count++;
        }
        return $info;
        /*
        if(mysqli_num_rows($result)==1)
        {

            $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
            if($is_push)//测试中，请勿投入使用
            {
                $push_text=array();
                $last_id = $row['last_sysmessage_pushed'];
                $message = mysqli_query($con,"SELECT * FROM system_message WHERE id > $last_id");
                if(mysqli_num_rows($result)<1)
                {
                    return 1;
                }
                while($row = mysqli_fetch_array($result,MYSQLI_ASSOC))
                {
                    if($row['push_text']!="")
                        array_push($push_text,$row['push_text']);
                }
                return $push_text;
            }
            else
            {
                $count=1;
                $mes_text=array();
                $last_id = $row['last_sysmessage_read'];
                $message = mysqli_query($con,"SELECT * FROM system_message order by id desc limit 10");
                if(mysqli_num_rows($result)<1)
                {
                    return 1;
                }
                while($row = mysqli_fetch_array($message,MYSQLI_ASSOC))
                {

                    if($only_not_read)
                    {
                        if($row['id']>=$last_id)
                        {
                            $mes_text[$count]['time']=$row['time'];
                            $mes_text[$count]['title']=$row['title'];
                            $mes_text[$count]['type']=GetNoticeType($row['type']);
                            $mes_text[$count]['detail']=$row['detail'];
                        }
                    }
                    else
                    {
                        $mes_text[$count]['time']=$row['time'];
                        $mes_text[$count]['title']=$row['title'];
                        $mes_text[$count]['type']=GetNoticeType($row['type']);
                        $mes_text[$count]['detail']=$row['detail'];
                    }
                    $count++;
                }
                return $mes_text;
            }
        }
        else
        {
            return false;
        }
        */
    }


    function mksecret($len = 20) {//share secret制作
        $ret = "";
        for ($i = 0; $i < $len; $i++)
            $ret .= chr(mt_rand(100, 120));
        return $ret;
    }

}
