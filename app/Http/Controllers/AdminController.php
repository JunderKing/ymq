<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models;
use App\Utils;

class AdminController extends Controller
{
    // 用户管理
    public function userList() {
        $curUserObj = Models\User::$curUserObj;
        if (!$curUserObj->club_id) {
            return $this->error(202);
        }
        // 查询用户课时
        $courseColl = Models\Course::where('club_id', $curUserObj->club_id)->get();
        $courseIds = array_column($courseColl->toArray(), 'id');
        $courseList = [];
        foreach ($courseColl as $courseObj) {
            $courseList[] = [
                'courseId' => $courseObj->id,
                'title' => $courseObj->title,
            ];
        }
        $userCourseDict = [];
        if ($courseIds) {
            $userCourseColl = Models\UserCourse::selectRaw('user_id, sum(total_lesson) as total_lesson, sum(used_lesson) as used_lesson')
                ->whereIn('course_id', $courseIds)->groupBy('user_id')->get();
            foreach($userCourseColl as $userCourseObj) {
                $userCourseDict[$userCourseObj->user_id] = $userCourseObj;
            }
        }
        $userColl = Models\User::get();
        $userDict = [];
        foreach ($userColl as $userObj) {
            $userCourseObj = @$userCourseDict[$userObj->id] ?: null;
            $name = $userObj->realName ?: $userObj->nickname;
            $totalLesson = $userCourseObj ? $userCourseObj->total_lesson : 0;
            $usedLesson = $userCourseObj ? $userCourseObj->used_lesson : 0;
            $userData = [
                'userId' => $userObj->id,
                'avatar' => $userObj->avatar,
                'name' => $name,
                'nickname' => $userObj->nickname,
                'realName' => $userObj->real_name,
                'totalLesson' => $totalLesson,
                'remainLesson' => $totalLesson - $usedLesson,
            ];
            $letter = Utils\BaseUtil::getFirstChar($name);
            if (is_array(@$userDict[$letter])) {
                $userDict[$letter][] = $userData;
            } else {
                $userDict[$letter] = [$userData];
            }
        }

        return $this->output([
            'courseList' => $courseList,
            'userDict' => $userDict
        ]);
    }

    public function userLessonAssign() {
        $courseId = $this->param('courseId', 'required|int|min:1');
        $lessonCount = $this->param('lessonCount', 'required|int|min:1');
        $userId = $this->check('userId', 'required|int|min:1');

        $curUserObj = Models\User::$curUserObj;
        $courseObj = Models\Course::findOrFail($courseId);
        if ($courseObj->club_id != $curUserObj->club_id) {
            return $this->error(201);
        }

        // 加课
        $userCourseObj = Models\UserCourse::firstOrCreate([
            'user_id' => $userId,
            'course_id' => $courseId,
        ]);
        \DB::beginTransaction();
        if ($curUserObj->trial_status == 0) {
            Models\User::where([['id', $userId], ['trial_status', 0]])->update(['trial_status' => 1]);
        }
        Models\UserCourse::where('id', $userCourseObj->id)->increment('total_lesson', $lessonCount);
        \DB::commit();

        return $this->output();
    }
    // 课程管理
    public function courseList() {
        $curUserObj = Models\User::$curUserObj;

        // 获取课程列表
        $courseColl = Models\Course::where('club_id', $curUserObj->club_id)->orderBy('updated_at', 'desc')->get();
        $courseIds = array_column($courseColl->toArray(), 'id');
        $courseLessonDict = Models\Lesson::calcCourseLesson($courseIds);
        // 获取发起人信息
        $userColl = Models\User::whereIn('id', array_column($courseColl->toArray(), 'user_id'))->get();
        $userDict = [];
        foreach ($userColl as $userObj) {
            $userDict[$userObj->id] = $userObj;
        }
        $courseList = [];
        foreach ($courseColl as $courseObj) {
            $courseLessonData = @$courseLessonDict[$courseObj->id];
            $userObj = $userDict[$courseObj->user_id];
            $courseList[] = [
                'courseId' => $courseObj->id,
                'avatar' => $userObj->avatar,
                'title' => $courseObj->title,
                'intro' => $courseObj->intro,
                'totalLesson' => @$courseLessonData['totalLesson'] ?: 0,
                'pendingLesson' => @$courseLessonData['pendingLesson'] ?: 0,
                'statusText' => '报名中',
                'createTs' => strtotime($courseObj->created_at),
            ];
        }

        return $this->output(['courseList' => $courseList]);
    }
    public function courseDetail() {
        $courseId = $this->check('courseId', 'required|int|min:1');
        $curUserObj = Models\User::$curUserObj;
        // 查询课程信息
        $courseObj = Models\Course::findOrFail($courseId);
        // 查询创建者信息
        $leaderObj = Models\User::findOrFail($courseObj->user_id);
        // 查询课节列表
        $lessonColl = Models\Lesson::where('course_id', $courseObj->id)->orderBy('start_time', 'desc')->get();
        // 查询地址信息
        $addressColl = Models\Address::get();
        $addressDict = [];
        $addressList = [];
        foreach ($addressColl as $addressObj) {
            $addressDict[$addressObj->id] = $addressObj;
            $addressList[] = [
                'addressId' => $addressObj->id,
                'address' => $addressObj->address,
            ];
        }
        $lessonList = [];
        $myNum = 0;
        foreach ($lessonColl as $lessonObj) {
            $addressObj = $addressDict[$lessonObj->address_id];
            $lessonList[] = [
                'lessonId' => $lessonObj->id,
                'startTs' => strtotime($lessonObj->start_time),
                'totalNum' => $lessonObj->total_num,
                'takenNum' => $lessonObj->taken_num,
                'duration' => $lessonObj->duration,
                'status' => strtotime($lessonObj->start_time) > time() ? 1 : 0,
                'addressData' => [
                    'addressId' => $addressObj->id,
                    'address' => $addressObj->address,
                    'latitude' => $addressObj->latitude,
                    'longitude' => $addressObj->longitude,
                ]
            ];
        }

        return $this->output([
            'myNum' => $myNum,
            'realName' => $curUserObj ? $curUserObj->real_name : '',
            'title' => $courseObj->title,
            'intro' => $courseObj->intro,
            'createTs' => strtotime($courseObj->created_at),
            'leaderData' => [
                'avatar' => $leaderObj->avatar,
                'nickname' => $leaderObj->nickname,
            ],
            'lessonList' => $lessonList,
            'addressList' => $addressList
        ]);
    }
    public function courseUpdate() {
        $courseId = $this->param('courseId', 'nullable|int', null);
        $title = $this->param('title', 'required');
        $intro = $this->param('intro', 'required');

        $curUserObj = Models\User::$curUserObj;
        if ($courseId) {
            Models\Course::where([['id', $courseId], ['club_id', $curUserObj->club_id]])->update([
                'title' => $title,
                'intro' => $intro,
                'status' => 1
            ]);
        } else {
            $courseObj = Models\Course::create([
                'club_id' => $curUserObj->club_id,
                'user_id' => $curUserObj->id,
                'title' => $title,
                'intro' => $intro,
                'status' => 1
            ]);
            $courseId = $courseObj->id;
        }

        return $this->output(['courseId' => $courseId]);
    }

    public function courseDelete() {
        $courseId = $this->check('courseId', 'required|int|min:1');
        return $this->output();
    }

    // 课节管理
    public function lessonUpdate()
    {
        $courseId = $this->param('courseId', 'required|int|min:1');
        $lessonId = $this->param('lessonId', 'nullable|int', 0);
        $startTime = $this->param('startTime', 'required');
        $duration = $this->param('duration', 'required');
        $totalNum = $this->param('totalNum', 'required|int|min:1');
        $addressId = $this->check('addressId', 'required|int|min:1');

        if ($lessonId > 0) {
            // 更新课程
            Models\Lesson::where('id', $lessonId)->update([
                'course_id' => $courseId,
                'start_time' => $startTime,
                'duration' => $duration,
                'total_num' => $totalNum,
                'address_id' => $addressId,
            ]);
        } else {
            // 新建课程
            $lessonObj = Models\Lesson::create([
                'course_id' => $courseId,
                'start_time' => $startTime,
                'duration' => $duration,
                'total_num' => $totalNum,
                'address_id' => $addressId,
            ]);
            $lessonId = $lessonObj->id;
        }

        return $this->output(['lessonId' => $lessonId]);
    }
}
