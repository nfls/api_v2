<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PKPass\PKPass;
use Illuminate\Support\Facades\DB;
use Response;
use Cookie;

class ICSpecialController extends Controller
{
    //
    function generatePass(Request $request)
    {
        if($request->has("token"))
            $id = UserCenterController::GetUserId($request->input("token"));
        else
            $id = UserCenterController::GetUserId(Cookie::get("token"));

        $info = DB::connection("mysql_ic")->table("ic_activity")->where(["user_id"=>$id])->first();
        if(is_null($info->auth_code))
            abort(403);
        $name = DB::connection("mysql_ic")->table("ic_auth")->where(["id"=>$id])->get();
        if(count($name) != 1)
            abort(403);
        if($name[0]->enabled != 1)
            abort(403);
        $phone = UserCenterController::GetUserMobile($id);
        $pass = new PKPass('/etc/cert/pkpass.p12', '');
        $auth = new UserCenterController();
        $aInfo = $auth->ICInfo($id);
        $data = array(
            'formatVersion' => 1,
            'passTypeIdentifier' => 'pass.halloween.ic.nfls',
            'serialNumber' => Cookie::get("token"),
            'teamIdentifier' => 'K2P3533G4D',
            'webServiceURL' => 'https://api.nfls.io/passes/',
            'authenticationToken' => 'vxwxd7J8AlNNFPS8k0a0FfUFtq0ewzFdc',
            'relevantDate' => '2017-11-08T17:00+08:00',
            'locations' =>
                array(
                    0 =>
                        array(
                            'longitude' => 118.79456999999999,
                            'latitude' => 32.044420000000002,
                        ),
                ),
            'barcode' =>
                array(
                    'message' => $info->auth_code,
                    'format' => 'PKBarcodeFormatPDF417',
                    'messageEncoding' => 'iso-8859-1',
                ),
            'organizationName' => '南京外国语学校IB&A-Level国际课程中心',
            'description' => '南京外国语学校万圣节门票',
            'foregroundColor' => 'rgb(255, 255, 255)',
            'labelColor' => ' rgb(255, 255, 255)',
            'eventTicket' =>
                array(
                    'primaryFields' =>
                        array(
                            0 =>
                                array(
                                    'key' => 'attendee',
                                    'label' => '姓名',
                                    'value' => $name[0]->chnName,
                                ),
                        ),
                    'secondaryFields' =>
                        array(
                            0 =>
                                array(
                                    'key' => 'dates',
                                    'label' => '时间',
                                    'value' => '2017年11月8日 周三 17:30 - 20:30',
                                ),
                        ),
                    'auxiliaryFields' =>
                        array(
                            0 =>
                                array(
                                    'key' => 'location',
                                    'label' => '地点',
                                    'value' => '南京外国语学校太平北路校区',
                                ),
                            1 =>
                                array(
                                    'key' => 'type',
                                    'label' => '活动',
                                    'value' => '万圣节',
                                ),
                        ),
                    'backFields' =>
                        array(
                            0 =>
                                array(
                                    'key' => 'class',
                                    'label' => '个人',
                                    'value' => '您的班级：'.$aInfo["tmpClass"] . ' ；您的英文名：'.$name[0]->engName,
                                    'attributedValue' => '您的班级：'.$aInfo["tmpClass"] . ' ；您的英文名：'.$name[0]->engName
                                ),
                            1 =>
                                array(
                                    'key' => 'face',
                                    'label' => '票面',
                                    'value' => '请您核对您的票面信息，尤其是姓名、班级信息以及手机号，是否准确无误，如果有误，请及时向我们的客服反馈并更正',
                                    'attributedValue' => '请您核对您的票面信息，尤其是姓名、班级信息以及手机号，是否准确无误，如果有误，请及时向我们的客服反馈并更正'
                                ),
                            2 =>
                                array(
                                    'key' => 'notice',
                                    'label' => '通知',
                                    'value' => '相关活动通知将通过短信发送至您在购票或实名认证时填写的手机['. (string)$phone. ']',
                                    'attributedValue' => '相关活动通知将通过短信发送至您在购票或实名认证时填写的手机['. (string)$phone. ']'
                                ),
                            3 =>
                                array(
                                    'key' => 'check-in',
                                    'label' => '检票',
                                    'value' => '您需要凭票面上的二维码进场，由于二维码会随时间变化，请不要截图并打印',
                                    'attributedValue' => '您需要凭票面上的二维码进场，由于二维码会随时间变化，请不要截图并打印',
                                ),
                            4 =>
                                array(
                                    'key' => 'cs',
                                    'label' => '客服',
                                    'value' => '如果您对电子门票有使用上的问题，请添加南外人小客服QQ[2965860844]进行咨询，活动安排等问题请咨询相关负责人',
                                    'attributedValue' => '如果您对电子门票有使用上的问题，请添加南外人小客服QQ[2965860844]进行咨询，活动安排等问题请咨询相关负责人',
                                ),

                        ),
                ),
        );
        $pass->setData($data);
        $pass->addFile('/usr/share/halloween/icon.png','icon.png');
        $pass->addFile('/usr/share/halloween/icon@2x.png','icon@2x.png');
        $pass->addFile('/usr/share/halloween/logo.png','logo.png');
        $pass->addFile('/usr/share/halloween/logo@2x.png','logo@2x.png');
        $pass->addFile('/usr/share/halloween/thumbnail.png','thumbnail.png');
        $pass->addFile('/usr/share/halloween/thumbnail@2x.png','thumbnail@2x.png');
        $pass->addFile('/usr/share/halloween/background.png','background.png');
        $pass->addFile('/usr/share/halloween/background@2x.png','background@2x.png');
        if(!$pass->create(true)) { // Create and output the PKPass
            return 'Error: ' . $pass->getError();
        }
        return;
    }
}
