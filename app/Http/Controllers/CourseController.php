<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models;

class CourseController extends Controller
{
    // 1 进行中，2 已截止，3 已结束，4 已取消
    public function createOrUpdate() {
        $courseId = $this->param('courseId', 'nullable|int', null);
        $title = $this->param('title', 'required');
        $intro = $this->param('intro', 'required');
        $lessonTotalNum = $this->param('lessonTotalNum', 'required');
        $lessonDuration = $this->check('lessonDuration', 'required');

        $curUserId = Models\User::$curUserId;
        if ($courseId) {
            Models\Course::where([['id', $courseId], ['user_id', $curUserId]])->update([
                'title' => $title,
                'intro' => $intro,
                'lesson_total_num' => $lessonTotalNum,
                'lesson_duration' => $lessonDuration,
                'status' => 1
            ]);
        } else {
            $courseObj = Models\Course::create([
                'user_id' => $curUserId,
                'title' => $title,
                'intro' => $intro,
                'lesson_total_num' => $lessonTotalNum,
                'lesson_duration' => $lessonDuration,
                'status' => 1
            ]);
            $courseId = $courseObj->id;
        }

        return $this->output(['courseId' => $courseId]);
    }

    public function delete() {
        $courseId = $this->check('courseId', 'required|int|min:1');
        return $this->output();
    }

    public function info() {
        $courseId = $this->check('courseId', 'required|int|min:1');
        // 查询课程信息
        $courseObj = Models\Course::findOrFail($courseId);
        // 查询课节信息
        $lessonColl = Models\Lesson::where('course_id', $courseId)->orderBy('start_time', 'desc')->get();
        // 查询地址信息
        $addressColl = Models\Address::whereIn('id', array_column($lessonColl->toArray(), 'address_id'))->get();
        $addressDict = [];
        foreach ($addressColl as $addressObj)
            $addressDict[$addressObj->id] = $addressObj;
        $lessonList = [];
        foreach($lessonColl as $lessonObj) {
            $addressObj = $addressDict[$lessonObj->address_id];
            $lessonList[] = [
                'lessonId' => $lessonObj->id,
                'startTs' => strtotime($lessonObj->start_time),
                'totalNum' => $lessonObj->total_num,
                'duration' => $lessonObj->duration,
                'addressData' => [
                    'addressId' => $addressObj->id,
                    'address' => $addressObj->address,
                    'latitude' => $addressObj->latitude,
                    'longitude' => $addressObj->longitude,
                ]
            ];
        }

        return $this->output([
            'title' => $courseObj->title,
            'intro' => $courseObj->intro,
            'lessonTotalNum' => $courseObj->lesson_total_num,
            'lessonDuration' => $courseObj->lesson_duration,
            'lessonList' => $lessonList
        ]);
    }

    public function detail() {
        $courseId = $this->check('courseId', 'required|int|min:1');
        $curUserObj = Models\User::$curUserObj;
        // 查询自己报名的课程
        $lessonIds = $curUserObj ? Models\Record::where('user_id', $curUserObj->id)->pluck('lesson_id')->toArray() : [];
        // 查询课程信息
        $courseObj = Models\Course::findOrFail($courseId);
        // 查询创建者信息
        $leaderObj = Models\User::findOrFail($courseObj->user_id);
        // 查询课节列表
        $lessonColl = Models\Lesson::where('course_id', $courseObj->id)->orderBy('start_time', 'desc')->get();
        $lessonList = [];
        $myNum = 0;
        foreach ($lessonColl as $lessonObj) {
            in_array($lessonObj->id, $lessonIds) && $myNum++;
            $lessonList[] = [
                'lessonId' => $lessonObj->id,
                'startTs' => strtotime($lessonObj->start_time),
                'totalNum' => $lessonObj->total_num,
                'takenNum' => $lessonObj->taken_num,
                'status' => $lessonObj->status,
                'joinStatus' => in_array($lessonObj->id, $lessonIds) ? 1 : 0,
            ];
        }

        return $this->output([
            'myNum' => $curUserObj ? count($lessonIds) : 0,
            'realName' => $curUserObj ? $curUserObj->real_name : '',
            'title' => $courseObj->title,
            'intro' => $courseObj->intro,
            'createTs' => strtotime($courseObj->created_at),
            'leaderData' => [
                'avatar' => $leaderObj->avatar,
                'nickname' => $leaderObj->nickname,
            ],
            'lessonList' => $lessonList,
        ]);
    }

    public function list() {
        // 获取课程列表
        $courseColl = Models\Course::orderBy('id', 'desc')->get();
        // 获取发起人信息
        $userColl = Models\User::whereIn('id', array_column($courseColl->toArray(), 'user_id'))->get();
        $userDict = [];
        foreach ($userColl as $userObj) {
            $userDict[$userObj->id] = $userObj;
        }
        $courseList = [];
        foreach ($courseColl as $courseObj) {
            $userObj = $userDict[$courseObj->user_id];
            $courseList[] = [
                'courseId' => $courseObj->id,
                'avatar' => $userObj->avatar,
                'title' => $courseObj->title,
//                'status' => $courseObj->status,
//                'statusText' => Models\Course::getStatusText($courseObj),
                'statusText' => '报名中',
                'createTs' => strtotime($courseObj->created_at),
            ];
        }

        return $this->output(['courseList' => $courseList]);
    }

}
