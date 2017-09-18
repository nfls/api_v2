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
                }
                break;
            case "rank":
                if($request->isMethod("get")){
                    $info = $this->getRank(self::GetUserId(Cookie::get("token"),true));
                }else if($request->isMethod("post") && $request->has("score")){
                    $info = $this->updateScore(self::GetUserId(Cookie::get("token")),$request->input("score"));
                }
                break;
            default:
                break;
        }
        $json_mes = array();
        if (!is_int($info) && (@is_null($info) || empty($info))) {
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
                $message = "请使用浏览器访问本页（微信可右上角然后使用浏览器打开）。";
            } else {
                $osInfo = $dd->getOs();
                $device = $dd->getDevice();
                $brand = $dd->getBrandName();
                $model = $dd->getModel();
                if ($osInfo['name'] == "iOS") {
                    $allow = false;
                    $message = "iOS用户请使用我们的<a href='https://app.nfls.io'>客户端</a>进行访问。";
                } else {
                    if ($clientInfo['name'] != "Chrome" && $clientInfo['name'] != "Chrome Mobile") {
                        $message = "本站建议使用Chrome进行访问，不保证对于其他浏览器完全兼容。";
                    } else {
                        if (version_compare($clientInfo['version'], "54.0", "<")) {
                            $message = "Chrome版本过老！请考虑升级。";
                        }
                    }
                }

            }

        }
        return array("allow" => $allow, "message" => $message);
    }


    static function CreateCaptcha($ip, $operation)
    {
        DB::connection("mysql_user")->table("user_session")->where("valid_before", "<", date('Y-m-d H:i:s'))->delete();
        $phraseBuilder = new PhraseBuilder(10);
        $builder = new CaptchaBuilder(null, $phraseBuilder);
        $builder->buildAgainstOCR($width = 300, $height = 100, $font = null);
        //header('Content-type: image/jpeg');
        $phrase = $builder->getPhrase();
        $time = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $session = self::random_str(16);
        DB::connection("mysql_user")->table("user_session")->insert(["phrase" => $phrase, "ip" => $ip, "valid_before" => $time, "session" => $session, "operation" => $operation]);
        $image = 'data:image/jpeg;base64,' . base64_encode($builder->get($quality = 100));
        return array("captcha" => $image, "session" => $session);
    }

    static function ConfirmCaptcha($session, $captcha, $operation)
    {
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
        if (!$this->ConfirmCaptcha($session, $captcha, "login"))
            return array("status" => "failure", "message" => "验证码无效或不正确");
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
            return array("status" => "failure", "message" => "用户名或密码不正确");
    }

    function UserRegister($email, $password, $username, $session, $captcha)
    {
        /*
        if(preg_match("[A-Za-z0-9_]+",$username)!=$username)
            return [""]
        */
        if (!$this->ConfirmCaptcha($session, $captcha, "register"))
            return array("status" => "failure", "message" => "验证码无效或不正确");
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
        if (!$this->CheckIfUserExists($id))
            $this->AddUser($id);
        $token = $this->CheckIfTokenExists($id);
        if (!$token)
            $token = $this->GenerateToken($id);
        if ($this->GetUserAssociatedIdById($id,"wiki") == -1)
            $this->CreateWikiAccountById($id);
        return ($token);
    }

    function RecoverPassword($email, $session, $captcha)
    {
        if (!$this->ConfirmCaptcha($session, $captcha, "recover"))
            return array("status" => "failure", "message" => "验证码无效或不正确");
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
            return array("status" => "failure", "message" => "未找到您的邮箱");
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
            default:
                abort(403);
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
        DB::connection("mysql_user")->table("user_list")->where(["id" => $id])->first();
        $messages = DB::connection("mysql_user")->table("system_message")->where(["receiver" => $id])->orWhere(["receiver" => -1])->get();
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

    function getFirstMessage($id){
        DB::connection("mysql_user")->table("user_list")->where(["id" => $id])->first();
        $message = DB::connection("mysql_user")->table("system_message")->where(["receiver" => $id])->orWhere(["receiver" => -1])->orderBy("id","desc")->first();
        $info['id'] = $message->id;
        $info['title'] = $message->title;
        $info['text'] = $message->detail;
        $info['push'] = $message->conf;
        return $info;
    }

    function getUnreadCount($id){
        $user = DB::connection("mysql_user")->table("user_list")->where(["id"=>$id])->first();
        $last = $user->last_sysmessage_read;
        $message = DB::connection("mysql_user")->table("system_message")->where("id",">",$last)->where(function($query) use($id){
            $query->where(["receiver" => $id])->orWhere(["receiver" => -1]);
        })->get();
        return count($message);
    }

    function getRank($id,$retrieve = true){
        $user = DB::connection("mysql_user")->table("user_list")->where(["id"=>$id])->first();
        $count = DB::connection("mysql_user")->table("user_list")->where("score",">",$user->score)->get();
        $count = count($count);
        if($retrieve){
            $after = DB::connection("mysql_user")->table("user_list")->select(["id","score"])->whereNotNull("lastPlayed")->orderBy("score","desc")->limit(10)->get();
            $ranks = array();
            $names = array();
            $scores = array();
            $rank = 0;
            $last = 0;
            $count = 0;
            foreach($after as $single){
                $count ++;
                if($single->score == $last){

                }else{
                    $rank = $count;
                    $last = $single->score;
                }
                array_push($names,$single->username = self::GetUserNickname($single->id));
                array_push($scores,$single->score);
                array_push($ranks,$rank);

            }
            return array("names"=>$names,"ranks"=>$ranks,"scores"=>$scores);
        } else {
            return array("count"=>$count+1,"score"=>$user->score);
        }

    }

    function updateScore($id,$input){
        $rank = $this->getRank($id,false);
        $array["playerBefore"] = null;
        $array["playerAfter"] = null;
        if($rank["score"]<$input || $input == 0){
            if($input>0){
                DB::connection("mysql_user")->table("user_list")->where(["id"=>$id])->update(["score"=>$input,"lastPlayed"=> date('Y-m-d H:i:s')]);
                $rank = $this->getRank($id,false);
            }
            $array["bestScore"] = $rank["score"];
            $array["bestRank"] = $rank["count"];
            if($input != 0){
                $array["nowRank"] = $array["bestRank"];
            }else{
                $count = DB::connection("mysql_user")->table("user_list")->where("score",">",$input)->get();
                $count = count($count);
                $array["nowRank"] = $count + 1;

            }

            $before = DB::connection("mysql_user")->table("user_list")->where("score",">",$rank["score"])->orderBy("score","asc")->first();
            $after = DB::connection("mysql_user")->table("user_list")->where("score","<",$rank["score"])->orderBy("score","desc")->first();
            if(!is_null($before)){
                $array["playerBefore"]["username"] = self::GetUserNickname($before->id);
                $array["playerBefore"]["score"] = $before->score;
            }else{
                $array["playerBefore"]["username"] = "";
                $array["playerBefore"]["score"] = "";
            }
            if(!is_null($after)){
                $array["playerAfter"]["username"] = self::GetUserNickname($after->id);
                $array["playerAfter"]["score"] = $after->score;
            }else{
                $array["playerAfter"] = [];
            }
        } else {
            $array["bestScore"] = $rank["score"];
            $array["bestRank"] = $rank["count"];
            $count = DB::connection("mysql_user")->table("user_list")->where("score",">",$input)->get();
            $count = count($count);
            $array["nowRank"] = $count + 1;

        }
        return $array;
    }
}
