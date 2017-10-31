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
        $id = UserCenterController::GetUserId(Cookie::get("token"));
        $info = DB::connection("mysql_ic")->table("ic_activity")->where(["user_id"=>$id])->first();
        if(is_null($info->auth_code))
            abort(403);
        $name = DB::connection("mysql_ic")->table("ic_auth")->where(["id"=>$id])->first();
        if(is_null($name->chnName))
            abort(403);
        $pass = new PKPass('/etc/cert/pkpass.p12', '');
        $data = array(
            'formatVersion' => 1,
            'passTypeIdentifier' => 'pass.halloween.ic.nfls',
            'serialNumber' => 'IO0001',
            'teamIdentifier' => 'K2P3533G4D',
            'webServiceURL' => 'https://example.com/passes/',
            'authenticationToken' => 'vxwxd7J8AlNNFPS8k0a0FfUFtq0ewzFdc',
            'relevantDate' => '2017-11-03T10:00+08:00',
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
                                    'value' => $name->chnName,
                                ),
                        ),
                    'secondaryFields' =>
                        array(
                            0 =>
                                array(
                                    'key' => 'dates',
                                    'label' => '时间',
                                    'value' => '2017年11月3日 周五 17:30 - 20:30',
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
                                    'value' => '万圣节测试',
                                ),
                        ),
                    'backFields' =>
                        array(
                            0 =>
                                array(
                                    'key' => 'check-in',
                                    'label' => '检票',
                                    'value' => '请在入场的检票处出示票面上的二维码，由于二维码会随时间变化，请不要截图并打印',
                                    'attributedValue' => '请在入场的检票处出示票面上的二维码，由于二维码会随时间变化，请不要截图并打印',
                                )
                        ),
                ),
        );
        $pass->setData($data);
        $pass->addFile('/usr/share/halloween/icon.png','icon.png');
        $pass->addFile('/usr/share/halloween/icon@2x.png','icon@2x.png');
        //$pass->addFile('/usr/share/halloween/logo.png','logo.png');
        //$pass->addFile('/usr/share/halloween/logo@2x.png','logo@2x.png');
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
