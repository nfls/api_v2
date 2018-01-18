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
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Log;

require_once '/usr/share/php/api_sdk/vendor/autoload.php';

use Aliyun\Core\Config;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;

class UserCenterController extends Controller
{
    public static function GetUserId($token)
    {
        $user = DB::connection("mysql_user")->table("user_list")->where("token", $token)->first();
        if (is_null($user))
            abort(404.2);
        return $user->id;
    }

    public static function GetUserNickname($id)
    {
        $user = DB::connection("mysql_forum")->table("nfls_users")->where("id", $id)->first();
        return $user->username;
    }

    public static function GetUserMobile($id)
    {
        $user = DB::connection("mysql_user")->table("user_list")->where("id", $id)->first();
        return $user->phone;
    }

    public static function GetUserEmail($id)
    {
        $user = DB::connection("mysql_forum")->table("nfls_users")->where("id", $id)->first();
        return $user->email;
    }

    public static function checkAdmin($id)
    {
        $user = DB::connection("mysql_user")->table("user_list")->where("id", $id)->first();
        return $user->isAdmin;
    }

    public static function isUserExist($id)
    {
        $user = DB::connection("mysql_forum")->table("nfls_users")->where("id", $id)->first();
        if (@is_null($user->username))
            return false;
        else
            return true;
    }

    function requestHandler(Request $request, $type)
    {
        Log::Info(Cookie::get("token").":".$type);
        switch ($type) {
            case "login":
                if ($request->only(['username', 'password', 'session', 'captcha'] && $request->isMethod("post")))
                    $info = $this->UserLogin($request->input("username"), $request->input("password"), $request->input("session"), $request->input("captcha"));
                break;
            case "recover":
                if ($request->only(['email', 'session', 'captcha']) && $request->isMethod("post"))
                    $info = $this->RecoverPassword($request->input("email"), $request->input("session"), $request->input("captcha"));
                break;
            case "register":
                if ($request->only(['username', 'password', 'email', "session", "captcha"]) && $request->isMethod("post"))
                    $info = $this->UserRegister($request->input("email"), $request->input("password"), $request->input("username"), $request->input("session"), $request->input("captcha"));
                break;
            case "username":
                if ($request->isMethod("get"))
                    $info = $this->GetUsernameById(self::GetUserId(Cookie::get('token')));
                break;
            case "wikiRegister":
                if ($request->isMethod("get")) {
                    $this->CreateWikiAccountById(self::GetUserId(Cookie::get('token')));
                    $info['status'] = "succeed";
                }
                break;
            case "icRegister":
                if ($request->isMethod("get")) {
                    $this->RegisterTypechoAccounts(self::GetUserId(Cookie::get('token')),"ic");
                    $info['status'] = "succeed";
                }
                break;
            case "alumniRegister":
                if ($request->isMethod("get")) {
                    $this->RegisterTypechoAccounts(self::GetUserId(Cookie::get('token')),"alumni");
                    $info['status'] = "succeed";
                }
                break;
            case "avatar":
                if ($request->isMethod("get"))
                    $info['url'] = $this->GetAvatarById($this->GetUserId(Cookie::get('token')));
                break;
            case "generalInfo":
                if ($request->isMethod("get"))
                    $info = $this->GetPersonalGeneralInfoById(self::GetUserId(Cookie::get('token')));
                break;
            case "forumInfo":
                if ($request->isMethod("get"))
                    $info = $this->GetPersonalForumInfoById(self::GetUserId(Cookie::get('token')));
                break;
            case "wikiInfo":
                if ($request->isMethod("get")){
                    $id = $this->GetUserAssociatedIdById(self::GetUserId(Cookie::get('token')), "wiki");
                    if($id == -1){
                        $this->CreateWikiAccountById(self::GetUserId(Cookie::get('token')));
                        $id = $this->GetUserAssociatedIdById(self::GetUserId(Cookie::get('token')));
                        $info = $this->GetUserWikiInfoByWikiId($id);
                    }else{
                        $info = $this->GetUserWikiInfoByWikiId($id);
                    }
                }
                break;
            case "systemMessage":
                if ($request->isMethod("get"))
                    $info = $this->GetSystemNoticeById(self::GetUserId(Cookie::get('token')));
                break;
            case "registerCaptcha":
                if ($request->isMethod("get"))
                    $info = $this->CreateCaptcha($_SERVER['REMOTE_ADDR'], "register");
                break;
            case "loginCaptcha":
                if ($request->isMethod("get"))
                    $info = $this->CreateCaptcha($_SERVER['REMOTE_ADDR'], "login");
                break;
            case "recoverCaptcha":
                if ($request->isMethod("get"))
                    $info = $this->CreateCaptcha($_SERVER['REMOTE_ADDR'], "recover");
                break;
            case "device":
                if ($request->isMethod("get"))
                    $info = $this->getDevice();
                break;
            case "notice":
                if ($request->isMethod("get"))
                    $info = $this->getNotice();
                break;
            case "get2faKey":
                if ($request->isMethod("get"))
                    $info = $this->get2fakey();
                break;
            case "enable2fa":
                if ($request->isMethod("post") && $request->only(['code', 'key'])) {
                    $info = $this->enable2fa(self::GetUserId(Cookie::get("token")), $request->input("code"), $request->input("key"));
                }
                break;
            case "regenToken":
                if ($request->isMethod("get")) {
                    $info = "ok";
                    $this->regenerateToken(self::GetUserId(Cookie::get("token")));;
                }
                break;
            case "card":
                if ($request->isMethod("get"))
                    $info = $this->getRenameCardCount(self::GetUserId(Cookie::get("token")));
                break;
            case "rename":
                if ($request->isMethod("post") && $request->has(['name'])) {
                    $info = $this->renameAccount(self::GetUserId(Cookie::get("token")), $request->input("name"));
                }
                break;
            case "count":
                if($request->isMethod("get")){
                    $info = $this->getUnreadCount(self::GetUserId(Cookie::get("token")));
                }
                break;
            case "last":
                if($request->isMethod("get")){
                    $info = $this->getFirstMessage(self::GetUserId(Cookie::get("token")));
                    $this->LoginProcess(self::GetUserId(Cookie::get("token")));
                }
                break;
            case "news":
                if($request->isMethod("get")){
                    $info = $this->getNews(self::GetUserId(Cookie::get("token")));
                }
                break;
            case "phone":
                if($request->has("code") && $request->isMethod("post")){
                    $info = $this->ConfirmPhone($request->input("phone"),self::GetUserId(Cookie::get("token")),$request->input("code"),$request->input("captcha"));
                }else{
                    $info = $this->PhoneCaptcha($request->input("phone"),self::GetUserId(Cookie::get("token")),$request->input("captcha"));
                }
                break;
            case "auth":
                if($request->has("token")){
                    $info = $this->getStatus(self::GetUserId($request->input("token")));
                }
                break;
            case "realname":
                if($request->isMethod("get")){
                    $info = $this->ICInfo(self::GetUserId(Cookie::get("token")));
                }else{
                    $info = $this->ICInfo(self::GetUserId(Cookie::get("token")),$request->input("chnName"),$request->input("engName"),$request->input("tmpClass"));
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
    function getStatus($id){
        $info = array();
        if(DB::connection("mysql_user")->table("user_list")->where(["id" => $id])->first()->phone == 0)
            $info["phone"] = false;
        else
            $info["phone"] = true;
        $ic = DB::connection("mysql_ic")->table("ic_auth")->where(["id" => $id])->first();
        if(is_null($ic)){
            $info["ic"] = false;
        }else{
            $info["ic"] = $ic->submitted;
        }
        return $info;
    }

    function ICInfo($id,$chnName = null,$engName = null, $tmpClass= null, $added = true){
        $preload = DB::connection("mysql_ic")->table("ic_auth")->where(["id"=>$id])->first();
        if(is_null($preload)){
            if($added)
                DB::connection("mysql_ic")->table("ic_auth")->insert(["id"=>$id]);
            else
                return array("chnName"=>"","engName"=>"","enabled"=>0,"tmpClass"=>"","submitted"=>0);
        }else if(!is_null($chnName) && !$preload->enabled){
            DB::connection("mysql_ic")->table("ic_auth")->where(["id"=>$id])->update(["submitted"=>true,"chnName"=>$chnName,"engName"=>$engName,"tmpClass"=>$tmpClass]);
        }
        $info = DB::connection("mysql_ic")->table("ic_auth")->where(["id"=>$id])->first();
        $array["chnName"] = $info->chnName;
        $array["engName"] = $info->engName;
        $array["enabled"] = $info->enabled;
        $array["submitted"] = $info->submitted;
        if($info->enabled){
            $array["tmpClass"] = $info->year ." 届 ";
            switch($info->class){
                case 1:
                    $class = "IB1班";
                    break;
                case 2:
                    $class = "IB2班";
                    break;
                case 3:
                    $class = "剑桥1班";
                    break;
                case 4:
                    $class = "剑桥2班";
                    break;
                default:
                    $array["tmpClass"] = "老师";
                    $class = "";
                    break;
            }
            $array["tmpClass"] = $array["tmpClass"] . $class;
        }else{
            $array["tmpClass"] = $info->tmpClass;
        }
        return $array;
    }
    function getRenameCardCount($id)
    {
        return DB::connection("mysql_user")->table("user_list")->select("rename_cards")->where(["id" => $id])->first();
    }

    function renameAccount($id, $name)
    {
        $count = DB::connection("mysql_user")->table("user_list")->select("rename_cards")->where(["id" => $id])->first()->rename_cards;
        if ($count < 1)
            abort(404);
        else
            $count--;
        $matches = array();
        preg_match('/[A-Za-z0-9_\-\x{0800}-\x{9fa5}]{3,16}/u', $name, $matches);
        //var_dump($matches);
        if ($matches[0] != $name)
            abort(403);
        if (!@is_null(DB::connection("mysql_forum")->table("nfls_users")->where(["username" => $name])->first()->id)){
            abort(403);
        }
        DB::connection("mysql_forum")->table("nfls_users")->where(["id" => $id])->update(["username" => $name]);
        if ($this->GetUserAssociatedIdById($id, "wiki") > 0) {
            $name = str_replace("_", " ", $name);
            if (substr($name, 0, 1) === mb_substr($name, 0, 1)){
                $name = ucfirst($name);
            }
            DB::connection("mysql_wiki")->table("wiki_user")->where(["user_id" => $this->GetUserAssociatedIdById($id, "wiki")])->update(["user_name" => $name]);
        }
        DB::connection("mysql_user")->table("user_list")->where(["id" => $id])->update(["rename_cards" => $count]);
        return "ok";

    }


    function regenerateToken($id)
    {
        $this->GenerateToken($id);
    }

    function get2fakey()
    {
        $id = self::GetUserId(Cookie::get("token"));
        $user = DB::connection("mysql_user")->table("user_list")->where(["id" => $id])->first();
        if (@is_null($user->{'2fa'}) || $user->{'2fa'} != "") {
            return null;
        }
        $google2fa = new Google2FA();
        $key = $google2fa->generateSecretKey();
        $google2fa_url = $google2fa->getQRCodeGoogleUrl(
            'nfls.io',
            self::GetUsernameById($id),
            $key
        );
        return array("img" => $google2fa_url, "code" => $key);
    }

    function enable2fa($id, $code, $key)
    {
        $user = DB::connection("mysql_user")->table("user_list")->where(["id" => $id])->first();
        if (@is_null($user->{'2fa'}) || $user->{'2fa'} != "") {
            return null;
        }
        if (@is_null($code)) {
            return null;
        }
        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($key, $code);
        if ($valid) {
            DB::connection("mysql_user")->table("user_list")->where(["id" => $id])->update(["2fa" => $key]);
            return "ok";
        } else {
            return null;
        }
    }

    function getDevice()
    {
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
            return array("info" => $clientInfo, "os" => $osInfo, "device" => $device, "brand" => $brand, "model" => $model);
        }
    }

    function getNotice()
    {
        $allow = true;
        $message = "";
        $dd = new DeviceDetector($_SERVER['HTTP_USER_AGENT']);
        $dd->parse();
        if ($dd->isBot()) {
            $botInfo = $dd->getBot();
            //$message = "禁止非浏览器访问！";
        } else {
            $clientInfo = $dd->getClient(); // holds information about browser, feed reader, media player, ...
            if ($clientInfo["type"] != "browser") {
                $allow = false;

                $message = "请使用浏览器访问本页（可右上角然后使用浏览器打开）。<br/>Please use the browser to open this page. (For wechat, you can click the right top button and choose open with browser";
            } else {
                $osInfo = $dd->getOs();
                $device = $dd->getDevice();
                $brand = $dd->getBrandName();
                $model = $dd->getModel();
                if ($osInfo['name'] == "iOS") {
                    $allow = false;
                    $message = "iOS用户请使用我们的<a href='https://app.nfls.io'>客户端</a>进行访问。 <br/>For iOS devices, please use our <a href='https://app.nfls.io'>app</a> to access our website.";
                } else if ($osInfo['name'] == "Android"){
                    $message = "";
                } else {
                    if ($clientInfo['name'] != "Chrome" && $clientInfo['name'] != "Chrome Mobile") {
                        $message = "本站建议使用Chrome进行访问，不保证对于其他浏览器完全兼容。 <br/>We suggest using Chrome to access our website, and we do not guarantee it is compatible on any other browsers";
                    } else {
                        if (version_compare($clientInfo['version'], "54.0", "<")) {
                            $message = "Chrome版本过老！请考虑升级。 <br/>Your Chrome version is outdated! Please consider upgrading.";
                        }
                    }
                }

            }

        }
        return array("allow" => $allow, "message" =>  $message." 南外人2018年<a href='https://hqy.moe/2017/12/hello-2018/'>工作计划</a>");
    }


    static function CreateCaptcha($ip, $operation)
    {
        $image = 'data:image/png;base64,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';
        return array("captcha" => $image, "session" => "updateRequired");
    }

    function PhoneCaptcha($phone,$userId,$captcha){
        if(DB::connection("mysql_user")->table("user_list")->where(["id"=>$userId])->first()-> phone != 0){
            return false;
        }
        if(count(DB::connection("mysql_user")->table("user_list")->where(["phone"=>$phone])->get())>0){
            return false;
        }
        if(!is_numeric($phone)){
            return false;
        }
        DB::connection("mysql_user")->table("user_session")->where("valid_before", "<", date('Y-m-d H:i:s'))->delete();
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=6Lc0GTMUAAAAAN43IBOJp-hRdHAC5fVvf034twaJ&response='.$captcha);
        if(json_decode($verifyResponse)->success || $captcha == "app"){
            $code = mt_rand(100000, 999999);
            $ip = $_SERVER['REMOTE_ADDR'];
            DB::connection("mysql_user")->table("user_session")->insert(["phrase" => $code, "ip" => $ip, "valid_before" => date('Y-m-d H:i:s', strtotime('+5 minutes')), "session" => $userId, "operation" => $phone]);
            $this->sendMessage($phone,$code);
            return true;
        }else{
            return false;
        }

    }
    static function ConfirmCaptcha($session, $captcha, $operation)
    {
        if(is_null($session) || $session == ""){
            $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=6Lc0GTMUAAAAAN43IBOJp-hRdHAC5fVvf034twaJ&response='.$captcha);
            return json_decode($verifyResponse)->success;
        }else if($session == "app" ){
            return true;
        }else{
            DB::connection("mysql_user")->table("user_session")->where("valid_before", "<", date('Y-m-d H:i:s'))->delete();
            $valid = DB::connection("mysql_user")->table("user_session")->where(["session" => $session, "operation" => $operation, "phrase" => $captcha, "ip" => $_SERVER['REMOTE_ADDR']])->first();
            if (@is_null($valid->id)) {
                DB::connection("mysql_user")->table("user_session")->where(["session" => $session])->delete();
                return false;
            } else {
                DB::connection("mysql_user")->table("user_session")->where(["session" => $session, "operation" => $operation, "phrase" => $captcha, "ip" => $_SERVER['REMOTE_ADDR']])->delete();
                return true;
            }
        }
    }
    function ConfirmPhone($phone,$userId,$code,$captcha){
        if(DB::connection("mysql_user")->table("user_list")->where(["id"=>$userId])->first()-> phone != 0){
            return false;
        }
        if(count(DB::connection("mysql_user")->table("user_list")->where(["phone"=>$phone])->get())>0){
            return false;
        }
        if(!is_numeric($phone)){
            return false;
        }
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=6Lc0GTMUAAAAAN43IBOJp-hRdHAC5fVvf034twaJ&response='.$captcha);
        if(json_decode($verifyResponse)->success || $captcha == "app"){
            DB::connection("mysql_user")->table("user_session")->where("valid_before", "<", date('Y-m-d H:i:s'))->delete();
            $valid = DB::connection("mysql_user")->table("user_session")->where(["session" => (string)$userId, "operation" => $phone, "phrase" => $code, "ip" => $_SERVER['REMOTE_ADDR']])->first();
            if(!is_null($valid)){
                DB::connection("mysql_user")->table("user_session")->where(["session" => (string)$userId, "operation" => $phone, "phrase" => $code, "ip" => $_SERVER['REMOTE_ADDR']])->delete();
                DB::connection("mysql_user")->table("user_list")->where(["id"=>$userId])->update(["phone"=>$phone]);
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    static function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }

    function UserLogin($username, $password, $session, $captcha)
    {
        //if (!$this->ConfirmCaptcha($session, $captcha, "login"))
            //return array("status" => "failure", "message" => "Captcha invalid or incorrect.");
        $headers = array('content-type:application/vnd.api+json',);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://forum.nfls.io/api/token");
        curl_setopt($ch, CURLOPT_POST, 1);
        $post_data = '{"identification":"' . $username . '","password":"' . $password . '"}';
        if ($post_data != '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        $detail = (array)json_decode($file_contents, true);
        unset($ch);
        if (isset($detail['token']))
            return array("status" => "success", "token" => $this->LoginProcess($detail['userId']));
        else
            return array("status" => "failure", "message" => "南外人相关服务已于2018年1月17日终止，具体请见网页版公告。Service Discontinued.");
    }

    function UserRegister($email, $password, $username, $session, $captcha)
    {
        /*
        if(preg_match("[A-Za-z0-9_]+",$username)!=$username)
            return [""]
        */
        if (!$this->ConfirmCaptcha($session, $captcha, "register"))
            return array("status" => "failure", "message" => "Captcha invalid or incorrect.");
        $headers = array('content-type:application/vnd.api+json');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://forum.nfls.io/api/users");
        curl_setopt($ch, CURLOPT_POST, 1);
        $post_data = '{"data":{"attributes":{"username":"' . $username . '","email":"' . $email . '", "password":"' . $password . '"}}}';
        if ($post_data != '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        Log::info($file_contents);
        $detail = (array)json_decode($file_contents, true);
        if (isset($detail['data'])) {
            return array("status" => "success");
        }
        if (isset($detail['errors'])) {
            //die($file_contents);
            return array("status" => "failure", "code" => $detail['errors'][0]['status'], "general" => $detail['errors'][0]['code'], "message" => $detail['errors'][0]['detail']);
        }
    }

    function ForumLogin($username, $password, $token)
    {
        self::GetUserId($token);
        $headers = array('content-type:application/vnd.api+json',);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://forum.nfls.io/login");
        curl_setopt($ch, CURLOPT_POST, 1);
        $post_data = '{"identification":"' . $username . '","password":"' . $password . '"}';
        if ($post_data != '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        //echo $file_contents;
        preg_match_all("/Set\-cookie:([^\r\n]*)/i", $file_contents, $str);
        //echo json_encode($str);
        $opt_cookie = array();
        $opt_cookie[0] = substr($str[1][0], 1);
        $opt_cookie[1] = substr($str[1][1], 1);
        //echo $str[0][0];
        //curl_close($ch);
        unset($ch);
        return $opt_cookie;

    }

    function LoginProcess($id)
    {
        if($id<1)
            return;
        if (!$this->CheckIfUserExists($id))
            $this->AddUser($id);
        $token = $this->CheckIfTokenExists($id);
        if (!$token)
            $token = $this->GenerateToken($id);
        if ($this->GetUserAssociatedIdById($id,"wiki") == -1)
            $this->CreateWikiAccountById($id);
        if ($this->GetUserAssociatedIdById($id,"ic") == -1)
            $this->RegisterTypechoAccounts($id,"ic");
        if ($this->GetUserAssociatedIdById($id,"alumni") == -1)
            $this->RegisterTypechoAccounts($id,"alumni");
        return ($token);
    }

    function RecoverPassword($email, $session, $captcha)
    {
        if (!$this->ConfirmCaptcha($session, $captcha, "recover"))
            return array("status" => "failure", "message" => "Captcha invalid or incorrect.");
        $headers = array('content-type:application/vnd.api+json',);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://forum.nfls.io/api/forgot");
        curl_setopt($ch, CURLOPT_POST, 1);
        $post_data = '{"email":"' . $email . '"}';
        if ($post_data != '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        if ($file_contents == null)
            return array("status" => "success");
        else
            return array("status" => "failure", "message" => "Email does not exist.");
    }

    function CheckIfUserExists($id) //检查论坛用户是否存在于user表中
    {
        $user = DB::connection("mysql_user")->table("user_list")->where("id", $id)->first();
        if (is_null($user))
            return false;
        return true;
    }

    function AddUser($id)//添加论坛用户到user表中
    {
        DB::connection("mysql_user")->table("user_list")->insert(["id" => $id]);
    }

    function CheckIfTokenExists($id)//检查是否存在Token
    {
        $user = DB::connection("mysql_user")->table("user_list")->where("id", $id)->first();
        if (@is_null($user->token))
            return false;
        return $user->token;
    }

    function GenerateToken($id)//生成Token
    {
        do {
            $str1 = (string)microtime(true);
            $str2 = (string)$id;
            $final = $str1 . "顾平德穿女装" . $str2;
            $hash = hash("sha512", $final);
            $token = substr($hash, (strlen($hash) - 64));
            $db = DB::connection("mysql_user")->table("user_list")->where("token", $token)->first();
        } while (@!is_null($db->token));
        DB::connection("mysql_user")->table("user_list")->where(["id" => $id])->update(["token" => $token]);
        return $token;
    }

    function GetAssociatePassword($id)//生成密码
    {
        $user = DB::connection("mysql_user")->table("user_list")->where(["id" => $id])->first();
        if (@is_null($user->asso_password)) {
            $str1 = (string)microtime(true);
            $final = $str1 . "顾平德穿女装";
            $hash = hash("sha512", $final);
            $password = substr($hash, (strlen($hash) - 16));
            DB::connection("mysql_user")->table("user_list")->where(["id" => $id])->update(["asso_password" => $password]);
            return $password;
        } else {
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
        curl_setopt($ch, CURLOPT_URL, "https://forum.nfls.io/api/users/$id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        $detail = (array)json_decode($file_contents, true);
        $url = $detail['data']['attributes']['avatarUrl'];
        if (@is_null($url)) {
            $url = "https://center.nfls.io/center/js/no_head.png";
        }
        return $url;
    }

    function GetPersonalGeneralInfoById($id)//根据id获取综合信息
    {
        $user = DB::connection("mysql_forum")->table("nfls_users")->where(["id" => $id])->first();
        $info = array();
        $info['id'] = $user->id;
        $info['username'] = $user->username;
        $info['email'] = $user->email;
        $info['is_activated'] = $user->is_activated;
        $info['bio'] = $user->bio;
        $info['avatar_path'] = $user->avatar_path;
        $info['join_time'] = $user->join_time;
        return $info;
    }

    function GetPersonalForumInfoById($id)//根据id获取论坛信息
    {
        $user = DB::connection("mysql_forum")->table("nfls_users")->where(["id" => $id])->first();
        $info = array();
        $info['id'] = $user->id;
        $info['username'] = $user->username;
        $info['last_seen_time'] = $user->last_seen_time;
        $info['notifications_read_time'] = $user->notifications_read_time;
        $info['discussions_count'] = $user->discussions_count;
        $info['comments_count'] = $user->comments_count;
        return $info;
    }

    function GetUserWikiInfoByWikiId($id)//根据wiki_id获取wiki信息
    {
        if ($id == -1)
            return [];
        $user = DB::connection("mysql_wiki")->table("wiki_user")->where(["user_id" => $id])->first();
        $info = array();
        $info['user_id'] = $user->user_id;
        $info['user_name'] = $user->user_name;
        $info['user_real_name'] = $user->user_real_name;
        $info['user_touched'] = $user->user_touched;
        $info['user_registration'] = $user->user_registration;
        $info['user_editcount'] = $user->user_editcount;
        return $info;
    }

    function GetUserAssociatedIdById($id, $service)
    {
        $user = DB::connection("mysql_user")->table("user_list")->where("id", $id)->first();
        switch ($service) {
            case "wiki":
                return $user->wiki_account;
                break;
            case 'ic':
                return $user->ib_account;
                break;
            case 'alumni':
                return $user->alumni_account;
                break;
            default:
                abort(403);
                break;
        }
    }

    function CreateWikiAccountById($id)//注册wiki账户
    {
        if ($this->GetUserAssociatedIdById($id, "wiki") != -1)
            abort(403);

        $info = $this->GetPersonalGeneralInfoById($id);
        $username = $info['username'];
        $password = $this->GetAssociatePassword($id);
        $command = "php /var/www/nfls-wiki/maintenance/createAndPromote.php " . escapeshellarg($username) . " " . escapeshellarg($password);
        exec(escapeshellcmd($command));
        $username = ucfirst(str_replace("_", " ", $username));
        $user = DB::connection("mysql_wiki")->table("wiki_user")->where(["user_name" => $username])->first();
        DB::connection("mysql_user")->table("user_list")->where(["id" => $id])->update(["wiki_account" => $user->user_id]);
        return array("status" => "success");
    }

    function RegisterTypechoAccounts($id,$place){
        if($this->GetUserAssociatedIdById($id,$place) != -1)
            abort(403);
        if($place == "alumni"){
            $db = "mysql_alumni";
            $ac = "alumni_account";
        }else{
            $db = "mysql_ic";
            $ac = "ib_account";
        }
        $info = $this->GetPersonalGeneralInfoById($id);
        $username = $info['username'];
        $email = $info['email'];
        DB::connection($db)->table("typecho_users")->insert(["mail"=>$email,"screenName"=>$username,"name"=>$username,"group"=>"visitor","created"=>time()]);
        $user = DB::connection($db)->table("typecho_users")->where(["name"=>$username])->first();
        DB::connection("mysql_user")->table("user_list")->where(["id"=>$id])->update([$ac=>$user->uid]);
        return array("status" => "success");
    }



    static function GetNoticeType($type)//获取通知类型
    {
        switch ($type) {
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
            case "5":
                return "推广";
                break;
            default:
                return "";
                break;
        }
    }

    function GetSystemNoticeById($id)//获取主站通知或推送，并根据Token记录已读信息
    {
        $messages = DB::connection("mysql_user")->table("system_message")->where(["place"=>1])->where(function ($query) use($id) {
            $query->where(["receiver" => $id])->orWhere(["receiver" => -1]);
        })->get();
        $user = DB::connection("mysql_user")->table("user_list")->where(["id"=>$id])->first();
        $last = $user->last_sysmessage_read;
        $count = 0;
        $c = 0;
        foreach ($messages as $message) {
            $info[$count]['time'] = $message->time;
            $info[$count]['title'] = $message->title;
            $info[$count]['type'] = $this->GetNoticeType($message->type);
            $info[$count]['detail'] = $message->detail;
            $info[$count]['push'] = $message->conf;
            if($last>=$message->id){
                $info[$count]['isRead'] = true;
            } else {
                $info[$count]['isRead'] = false;
            }
            $c = max($c,$message->id);
            $count++;
        }
        DB::connection("mysql_user")->table("user_list")->where(["id" => $id])->update(["last_sysmessage_read"=>$c]);
        return $info;
    }

    function getNews($id){

        $messages = DB::connection("mysql_user")->table("system_message")->where(["place"=>3])->where(function ($query) use($id) {
            $query->where(["receiver" => $id])->orWhere(["receiver" => -1]);
        })->orderBy("priority","desc")->orderBy("id","desc")->limit(10)->select("type","title","detail","img","conf","time")->get();
        $info = array();
        $count = 0;
        foreach ($messages as $message) {
            if($message->type == -1){
                $exist = DB::connection("mysql_ic")->table("ic_activity")->where(["user_id"=>$id])->get();
                if(count($exist) != 1)
                    continue;
                else
                    $info[$count]['type'] = "活动";
            }else{
                $info[$count]['type'] = $this->GetNoticeType($message->type);
            }
            $info[$count]['time'] = $message->time;
            $info[$count]['title'] = $message->title;
            $info[$count]['detail'] = $message->detail;
            $info[$count]['conf'] = $message->conf;
            $info[$count]['img'] = $message->img;
            $count++;
            break;//Note: Only show the first one.
        }
        return $info;
    }

    function getFirstMessage($id){
        $message = DB::connection("mysql_user")->table("system_message")->where(["place"=>1])->where(function ($query) use($id) {
            $query->where(["receiver" => $id])->orWhere(["receiver" => -1]);
        })->orderBy("id","desc")->first();
        $info['id'] = $message->id;
        $info['title'] = $message->title;
        $info['text'] = $message->detail;
        $info['push'] = $message->conf;
        return $info;
    }

    function getUnreadCount($id){
        $user = DB::connection("mysql_user")->table("user_list")->where(["id"=>$id])->first();
        $last = $user->last_sysmessage_read;
        $message = DB::connection("mysql_user")->table("system_message")->where("id",">",$last)->where(["place"=>1])->where(function($query) use($id){
            $query->where(["receiver" => $id])->orWhere(["receiver" => -1]);
        })->get();
        return count($message);
    }

    function sendMessage($phone,$code){
        $demo = new SmsDemo(
            "LTAIP9SuQgddEG0f",
            "HjWeMlsrGgEXunUSkJ54fBowbIqhf3"
        );
        $response = $demo->sendSms(
            "南外人", // 短信签名
            "SMS_104720015", // 短信模板编号
            $phone, // 短信接收者
            Array(  // 短信模板中字段的值
                "code"=>$code
            )
        );
        return $response;
    }


}

/**
 * Class SmsDemo
 *
 * @property \Aliyun\Core\DefaultAcsClient acsClient
 */
class SmsDemo
{

    /**
     * 构造器
     *
     * @param string $accessKeyId 必填，AccessKeyId
     * @param string $accessKeySecret 必填，AccessKeySecret
     */
    public function __construct($accessKeyId, $accessKeySecret)
    {

        Config::load();

        // 短信API产品名
        $product = "Dysmsapi";

        // 短信API产品域名
        $domain = "dysmsapi.aliyuncs.com";

        // 暂时不支持多Region
        $region = "cn-hangzhou";

        // 服务结点
        $endPointName = "cn-hangzhou";

        // 初始化用户Profile实例
        $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);

        // 增加服务结点
        DefaultProfile::addEndpoint($endPointName, $region, $product, $domain);

        // 初始化AcsClient用于发起请求
        $this->acsClient = new DefaultAcsClient($profile);
    }

    /**
     * 发送短信范例
     *
     * @param string $signName <p>
     * 必填, 短信签名，应严格"签名名称"填写，参考：<a href="https://dysms.console.aliyun.com/dysms.htm#/sign">短信签名页</a>
     * </p>
     * @param string $templateCode <p>
     * 必填, 短信模板Code，应严格按"模板CODE"填写, 参考：<a href="https://dysms.console.aliyun.com/dysms.htm#/template">短信模板页</a>
     * (e.g. SMS_0001)
     * </p>
     * @param string $phoneNumbers 必填, 短信接收号码 (e.g. 12345678901)
     * @param array|null $templateParam <p>
     * 选填, 假如模板中存在变量需要替换则为必填项 (e.g. Array("code"=>"12345", "product"=>"阿里通信"))
     * </p>
     * @param string|null $outId [optional] 选填, 发送短信流水号 (e.g. 1234)
     * @return stdClass
     */
    public function sendSms($signName, $templateCode, $phoneNumbers, $templateParam = null, $outId = null) {

        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();

        // 必填，设置雉短信接收号码
        $request->setPhoneNumbers($phoneNumbers);

        // 必填，设置签名名称
        $request->setSignName($signName);

        // 必填，设置模板CODE
        $request->setTemplateCode($templateCode);

        // 可选，设置模板参数
        if($templateParam) {
            $request->setTemplateParam(json_encode($templateParam));
        }

        // 可选，设置流水号
        if($outId) {
            $request->setOutId($outId);
        }

        // 发起访问请求
        $acsResponse = $this->acsClient->getAcsResponse($request);

        // 打印请求结果
        // var_dump($acsResponse);

        return $acsResponse;

    }

    /**
     * 查询短信发送情况范例
     *
     * @param string $phoneNumbers 必填, 短信接收号码 (e.g. 12345678901)
     * @param string $sendDate 必填，短信发送日期，格式Ymd，支持近30天记录查询 (e.g. 20170710)
     * @param int $pageSize 必填，分页大小
     * @param int $currentPage 必填，当前页码
     * @param string $bizId 选填，短信发送流水号 (e.g. abc123)
     * @return stdClass
     */
    public function queryDetails($phoneNumbers, $sendDate, $pageSize = 10, $currentPage = 1, $bizId=null) {

        // 初始化QuerySendDetailsRequest实例用于设置短信查询的参数
        $request = new QuerySendDetailsRequest();

        // 必填，短信接收号码
        $request->setPhoneNumber($phoneNumbers);

        // 选填，短信发送流水号
        $request->setBizId($bizId);

        // 必填，短信发送日期，支持近30天记录查询，格式Ymd
        $request->setSendDate($sendDate);

        // 必填，分页大小
        $request->setPageSize($pageSize);

        // 必填，当前页码
        $request->setCurrentPage($currentPage);

        // 发起访问请求
        $acsResponse = $this->acsClient->getAcsResponse($request);

        // 打印请求结果
        // var_dump($acsResponse);

        return $acsResponse;
    }

}
