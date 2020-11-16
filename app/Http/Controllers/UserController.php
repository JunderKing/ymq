<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utils;
use App\Models;

class UserController extends Controller {
    public function wxLogin() {
        $avatar = $this->param('avatar', 'required|string');
        $realName = $this->param('realName', 'nullable', '');
        $nickname = $this->param('nickname', 'required|string');
        $code = $this->check('code', 'required|string');
        $rtnData = Utils\WxUtil::login($code);
        $openId = $rtnData['openId'];
        $userObj = Models\User::updateOrCreate(['open_id' => $openId], [
            'avatar' => $avatar,
            'real_name' => $realName,
            'nickname' => $nickname,
        ]);
        $token = null;
        if ($userObj) {
            $token = Utils\AuthUtil::setToken($userObj->id);
        }

        return $this->output([
            'openId' => $rtnData['openId'],
            'avatar' => $avatar,
            'nickname' => $nickname,
            'clubId' => $userObj->club_id,
            'token' => $token,
        ]);
    }

    public function detail() {
        $curUserObj = Models\User::$curUserObj;
        $userData = null;
        if ($curUserObj) {
            $userCourseObj = Models\UserCourse::selectRaw('sum(total_lesson) as total_lesson, sum(used_lesson) as used_lesson')
                ->where('user_id', $curUserObj->id)->first();
            $totalLesson = @$userCourseObj->total_lesson ?: 0;
            $usedLesson = @$userCourseObj->used_lesson ?: 0;
            $userData = [
                'nickname' => $curUserObj->nickname,
                'avatar' => $curUserObj->avatar,
                'clubId' => $curUserObj->club_id,
                'totalLesson' => $totalLesson,
                'usedLesson' => $usedLesson,
                'trialStatus' => $curUserObj->trial_status,
            ];
        }

        return $this->output(['userData' => $userData]);
    }

    public function lessonRecord() {
        $curUserObj = Models\User::$curUserObj;
        $recordColl = Models\Record::where('user_id', $curUserObj->id)->get();
        $lessonIds = array_column($recordColl->toArray(), 'lesson_id');
        $lessonColl = Models\Lesson::whereIn('id', $lessonIds)->get();
        $lessonList = [];
        // 查询地址信息
        $addressColl = Models\Address::whereIn('id', array_column($lessonColl->toArray(), 'address_id'))->get();
        $addressDict = [];
        foreach ($addressColl as $addressObj) {
            $addressDict[$addressObj->id] = $addressObj;
        }
        foreach ($lessonColl as $lessonObj) {
            $addressObj = $addressDict[$lessonObj->address_id];
            $lessonList[] = [
                'lessonId' => $lessonObj->id,
                'startTs' => strtotime($lessonObj->start_time),
                'totalNum' => $lessonObj->total_num,
                'takenNum' => $lessonObj->taken_num,
                'status' => strtotime($lessonObj->start_time) > time() ? 1 : 0,
                'joinStatus' => 1,
                'addressData' => [
                    'addressId' => $addressObj->id,
                    'address' => $addressObj->address,
                    'latitude' => $addressObj->latitude,
                    'longitude' => $addressObj->longitude,
                ]
            ];
        }

        return $this->output([
            'lessonList' => $lessonList
        ]);
    }


    // ======================================================

    public function getTempToken() {
        $curUserId = Models\User::$curUserId;
        $tempToken = Utils\AuthUtil::setTempToken($curUserId);

        return $this->output(['tempToken' => $tempToken]);
    }

    public function getToken() {
        $tempToken = $this->check('tempToken', 'required|string');
        $userId = \Redis::get("TempUser_$tempToken");
        if (!$userId) {
            return $this->error(100);
        }
        // 清除tempToken
        Utils\AuthUtil::clearTempToken($userId, $tempToken);
        // 设置正式token
        $token = Utils\AuthUtil::setToken($userId);

        return $this->output(['token' => $token]);
    }

    public function wxMobile() {
        $mobile = $this->param('mobile', 'required|numeric');
        $curUserId = Models\User::$curUserId;
        Models\User::where(['id' => $curUserId])->update(['mobile' => $mobile]);

        return $this->output();
    }

    public function vcode() {
        $nation = $this->param('nation', 'nullable|int', 86);
        $mobile = $this->check('mobile', 'required|int');
        $type = $this->check('type', 'nullable|string', 'reg');

        if($type == 'login') {
            $userObj = Models\User::firstOrCreate(['mobile' => $mobile], [
                'nation' => $nation,
            ]);
            if ($userObj->role != 'admin') {
                return $this->error(204);
            }
        }

        $vcode = Models\Vcode::genVcode($nation, $mobile);
        // Models\Vcode::sendVcode($nation, $mobile, $vcode);
        $mobile_arr = ['+' . $nation . $mobile];
        Models\Vcode::sendSms($mobile_arr, $vcode, config('const.sms.template_ids.code'));

        return $this->output();
    }

    public function mobileLogin() {
        $openId = $this->param('openId', 'nullable|string', null);
        $realName = $this->param('realName', 'required|string');
        $nation = $this->param('nation', 'required|int');
        $mobile = $this->param('mobile', 'required|int');
        $vcode = $this->check('vcode', 'required|numeric');

        Models\Vcode::checkVcode($nation, $mobile, $vcode);
        $userObj = Models\User::updateOrCreate(['mobile' => $mobile], [
            'real_name' => $realName,
            'open_id' => $openId,
            'nation' => $nation,
            'last_login' => date('Y-m-d H:i:s'),
        ]);
        $token = Utils\AuthUtil::setToken($userObj->id);

        return $this->output(['token' => $token]);
    }

    public function setDetail() {
        $realName = $this->param('realName', 'required|string');
        $company = $this->param('company', 'required|string');
        $industry = $this->param('industry', 'required|int');
        $contact = $this->check('contact', 'required|string');

        $curUserId = Models\User::$curUserId;
        Models\User::where(['id' => $curUserId])->update([
            'real_name' => $realName,
            'company' => $company,
            'industry' => $industry,
            'contact' => $contact,
        ]);

        return $this->output();
    }
}
