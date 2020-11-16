<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models;

class CourseController extends Controller
{
    public function list() {
        // 获取课程列表
        $courseColl = Models\Course::orderBy('id', 'desc')->get();
        $courseIds = array_column($courseColl->toArray(), 'id');
        $courseLessonDict = Models\Lesson::calcCourseLesson($courseIds);
        // 获取俱乐部信息
        $clubColl = Models\Club::whereIn('id', array_column($courseColl->toArray(), 'club_id'))->get();
        $clubDict = [];
        foreach ($clubColl as $clubObj) {
            $clubDict[$clubObj->id] = $clubObj;
        }
        // 获取俱乐部信息
        $courseList = [];
        foreach ($courseColl as $courseObj) {
            $courseLessonData = @$courseLessonDict[$courseObj->id];
            if (!$courseLessonData) {
                continue;
            }
            $clubObj = $clubDict[$courseObj->club_id];
            $courseList[] = [
                'courseId' => $courseObj->id,
                'logo' => $clubObj->logo,
                'name' => $clubObj->name,
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

    public function detail() {
        $courseId = $this->check('courseId', 'required|int|min:1');
        $curUserObj = Models\User::$curUserObj;
        // 查询自己报名的课程
        $userCourseObj = $curUserObj ? Models\UserCourse::where([['user_id', $curUserObj->id], ['course_id', $courseId]])->first() : null;
        $myTotalLesson = $userCourseObj ? $userCourseObj->total_lesson : 0;
        $myUsedLesson = $userCourseObj ? $userCourseObj->used_lesson : 0;
        if ($myTotalLesson == 0 && $curUserObj && $curUserObj->trial_status == 0) {
            $myTotalLesson++;
        }
        $lessonIds = $curUserObj ? Models\Record::where('user_id', $curUserObj->id)->pluck('lesson_id')->toArray() : [];
        // 查询课程信息
        $courseObj = Models\Course::findOrFail($courseId);
        // 查询俱乐部信息
        $clubObj = Models\Club::findOrFail($courseObj->club_id);
        // 查询课节列表
        $lessonColl = Models\Lesson::where('course_id', $courseObj->id)->orderBy('start_time', 'desc')->get();
        // 查询地址信息
        $addressColl = Models\Address::whereIn('id', array_column($lessonColl->toArray(), 'address_id'))->get();
        $addressDict = [];
        foreach ($addressColl as $addressObj) {
            $addressDict[$addressObj->id] = $addressObj;
        }
        $lessonList = [];
        $myNum = 0;
        foreach ($lessonColl as $lessonObj) {
            if (time() - strtotime($lessonObj->start_time) > $lessonObj->duration * 60) {
                continue;
            }
            $addressObj = $addressDict[$lessonObj->address_id];
            in_array($lessonObj->id, $lessonIds) && $myNum++;
            $lessonList[] = [
                'lessonId' => $lessonObj->id,
                'startTs' => strtotime($lessonObj->start_time),
                'totalNum' => $lessonObj->total_num,
                'takenNum' => $lessonObj->taken_num,
                'status' => $lessonObj->status,
                'joinStatus' => in_array($lessonObj->id, $lessonIds) ? 1 : 0,
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
            'totalLesson' => $myTotalLesson,
            'usedLesson' => $myUsedLesson,
            'realName' => $curUserObj ? $curUserObj->real_name : '',
            'title' => $courseObj->title,
            'intro' => $courseObj->intro,
            'createTs' => strtotime($courseObj->created_at),
            'clubData' => [
                'logo' => $clubObj->logo,
                'name' => $clubObj->name,
            ],
            'lessonList' => $lessonList,
        ]);
    }

}
