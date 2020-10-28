<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models;

class ActivityController extends Controller
{
    // 1 进行中，2 已截止，3 已结束，4 已取消
    public function create() {
        $title = $this->param('title', 'required');
        $intro = $this->param('intro', 'required');
        $startTime = $this->param('startTime', 'required');
        $endTime = $this->param('endTime', 'required');
        $deadline = $this->param('deadline', 'required');
        $totalCount = $this->param('totalCount', 'required|int|min:1');
        $latitude = $this->param('latitude', 'nullable|numeric', 0);
        $longitude = $this->param('longitude', 'nullable', 0);
        $address = $this->check('address', 'nullable', '');

        $curUserId = Models\User::$curUserId;
        $activityObj = Models\Activity::create([
            'user_id' => $curUserId,
            'title' => $title,
            'intro' => $intro,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'deadline' => $deadline,
            'total_count' => $totalCount,
            'address' => $address,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'status' => 1
        ]);

        return $this->output(['activityId' => $activityObj->id]);
    }

    public function detail() {
        $activityId = $this->check('activityId', 'required|int|min:1');
        // 查询活动信息
        $activityObj = Models\Activity::findOrFail($activityId);
        // 查询创建者信息
        $leaderObj = Models\User::findOrFail($activityObj->user_id);
        // 查询报名记录
        $recordColl = Models\Record::where('activity_id', $activityObj->id)->orderBy('id', 'desc')->get();
        // 获取报名的用户信息
        $userColl = Models\User::whereIn('id', array_column($recordColl->toArray(), 'user_id'))->get();
        $userDict = [];
        foreach ($userColl as $userObj) {
            $userDict[$userObj->id] = $userObj;
        }
        $recordList = [];
        foreach ($recordColl as $recordObj) {
            $userObj = $userDict[$recordObj->user_id];
            $recordList[] = [
                'avatar' => $userObj->avatar,
                'nickname' => $userObj->nickname,
                'userCount' => $recordObj->user_count,
                'createTs' => strtotime($recordObj->created_at)
            ];
        }

        return $this->output([
            'title' => $activityObj->title,
            'intro' => $activityObj->intro,
            'address' => $activityObj->address,
            'latitude' => $activityObj->latitude,
            'longitude' => $activityObj->longitude,
            'startTs' => strtotime($activityObj->start_time),
            'endTs' => strtotime($activityObj->end_time),
            'deadlineTs' => strtotime($activityObj->deadline),
            'totalCount' => $activityObj->total_count,
            'occupiedCount' => $activityObj->occupied_count,
            'status' => $activityObj->status,
            'statusText' => Models\Activity::getStatusText($activityObj),
            'createTs' => strtotime($activityObj->created_at),
            'leaderData' => [
                'avatar' => $leaderObj->avatar,
                'nickname' => $leaderObj->nickname,
            ],
            'recordList' => $recordList,
        ]);
    }

    public function list() {
        // 获取活动列表
        $activityColl = Models\Activity::orderBy('id', 'desc')->get();
        // 获取发起人信息
        $userColl = Models\User::whereIn('id', array_column($activityColl->toArray(), 'user_id'))->get();
        $userDict = [];
        foreach ($userColl as $userObj) {
            $userDict[$userObj->id] = $userObj;
        }
        $activityList = [];
        foreach ($activityColl as $activityObj) {
            $userObj = $userDict[$activityObj->user_id];
            $activityList[] = [
                'activityId' => $activityObj->id,
                'avatar' => $userObj->avatar,
                'title' => $activityObj->title,
                'totalCount' => $activityObj->total_count,
                'occupiedCount' => $activityObj->occupied_count,
                'status' => $activityObj->status,
                'statusText' => Models\Activity::getStatusText($activityObj),
            ];
        }

        return $this->output(['activityList' => $activityList]);
    }

    // 1 报名成功 0 取消报名
    public function join() {
        $userCount = $this->param('userCount', 'nullable|int|min:1', 1);
        $activityId = $this->check('activityId', 'required|int|min:1');
        $curUserObj = Models\User::$curUserObj;
        // 是否已经报过
        $isExist = Models\Record::where([['activity_id', $activityId], ['user_id', $curUserObj->id]])->exists();
        if ($isExist) {
            return $this->error(301);
        }
        $activityObj = Models\Activity::findOrFail($activityId);
        \DB::beginTransaction();
        Models\Record::create([
            'activity_id' => $activityId,
            'user_id' => $curUserObj->id,
            'user_count' => $userCount,
            'status' => 1,
        ]);
        Models\Activity::where('id', $activityId)->increment('occupied_count', $userCount);
        \DB::commit();

        return $this->output(['activityId' => $activityObj->id]);
    }
}
