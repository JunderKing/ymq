<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models;
use App\Utils;

class LessonController extends Controller
{
    public function detail()
    {
        $lessonId = $this->check('lessonId', 'required|int|min:1');
        $lessonObj = Models\Lesson::findOrFail($lessonId);
        $courseObj = Models\Course::findOrFail($lessonObj->course_id);
        $addressObj = Models\Address::findOrFail($lessonObj->address_id);
        $recordColl = Models\Record::where('lesson_id', $lessonId)->orderBy('id', 'desc')->get();
        $userIds = array_column($recordColl->toArray(), 'user_id');
        $userColl = Models\User::whereIn('id', $userIds)->get();
        $userDict = [];
        foreach ($userColl as $userObj) {
            $userDict[$userObj->id] = $userObj;
        }
        $recordList = [];
        foreach($recordColl as $recordObj) {
            $userObj = $userDict[$recordObj->user_id];
            $recordList[] = [
                'avatar' => $userObj->avatar,
                'nickname' => $userObj->nickname,
                'createTs' => strtotime($recordObj->created_at),
            ];
        }

        return $this->output([
            'title' => $courseObj->title,
            'intro' => $courseObj->intro,
            'totalNum' => $lessonObj->total_num,
            'takenNum' => $lessonObj->taken_num,
            'startTs' => strtotime($lessonObj->start_time),
            'duration' => $lessonObj->duration,
            'address' => $addressObj->address,
            'latitude' => $addressObj->latitude,
            'longitude' => $addressObj->longitude,
            'recordList' => $recordList,
        ]);
    }

    public function delete()
    {
        $lessonId = $this->check('lessonId', 'required|int|min:1');
        $isExist = Models\Record::where('lesson_id', $lessonId)->exists();
        if ($isExist) {
            return $this->error(304);
        }
        Models\Lesson::where('id', $lessonId)->delete();
        return $this->output();
    }

    public function cancel()
    {
        $lessonId = $this->check('lessonId', 'required|int|min:1');
        $curUserObj = Models\User::$curUserObj;
        $recordObj = Models\Record::where([['lesson_id', $lessonId], ['user_id', $curUserObj->id]])->firstOrFail();
        $lessonObj = Models\Lesson::findOrFail($lessonId);

        // 开课前一小时不可取消
        $startTs = strtotime($lessonObj->start_time);
        if ($startTs - time() <= 3600) {
            return $this->error(306);
        }

        \DB::beginTransaction();
        if ($recordObj->is_trial) {
            $flag = Models\User::where([['id', $curUserObj->id], ['trial_status', 1]])->update(['trial_status' => 0]);
        } else {
            $flag = Models\UserCourse::where([['user_id', $curUserObj->id], ['course_id', $lessonObj->course_id], ['used_lesson', '>', 0]])
                ->decrement('used_lesson', 1);
        }
        if (!$flag) {
            \DB::rollback();
            return $this->error();
        }
        $flag = Models\Lesson::where([['id', $lessonId], ['taken_num', '>', 0]])->decrement('taken_num', 1);
        if (!$flag) {
            \DB::rollback();
            return $this->error();
        }
        Models\Record::where([['lesson_id', $lessonId], ['user_id', $curUserObj->id]])->delete();
        \DB::commit();

        return $this->output();
    }

    // 1 报名成功 0 取消报名
    public function join()
    {
        $lessonId = $this->check('lessonId', 'required|int|min:1');
        $curUserObj = Models\User::$curUserObj;
        $lessonObj = Models\Lesson::findOrFail($lessonId);

        $startTs = strtotime($lessonObj->start_time);
        if ($startTs <= time()) {
            return $this->error(305);
        }

        $userCourseObj = Models\UserCourse::where([['user_id', $curUserObj->id], ['course_id', $lessonObj->course_id]])->first();
        $remainLesson = $userCourseObj ? $userCourseObj->total_lesson - $userCourseObj->used_lesson : 0;
        if ($curUserObj->trial_status && $remainLesson <= 0) {
            return $this->error(203);
        }

        // 是否已经报过
        $isExist = Models\Record::where([['lesson_id', $lessonId], ['user_id', $curUserObj->id]])->exists();
        if ($isExist) {
            return $this->error(301);
        }
        // 是否还有剩余的位置
        if ($lessonObj->total_num <= $lessonObj->taken_num) {
            return $this->error(302);
        }

        \DB::beginTransaction();
        // 扣除用户课时
        $isTrial = 0;
        if ($remainLesson > 0) {
            $flag = Models\UserCourse::where([['id', $userCourseObj->id]])->whereRaw('total_lesson > used_lesson')
                ->increment('used_lesson', 1);
        } else {
            $flag = Models\User::where([['id', $curUserObj->d], ['trial_status', 0]])->update(['trial_status' => 1]);
            $isTrial = 1;
        }
        if (!$flag) {
            \DB::rollback();
            return $this->error(103);
        }
        // 更改上课人次
        $flag = Models\Lesson::where([['id', $lessonId]])->whereRaw('total_num > taken_num')
            ->increment('taken_num', 1);
        if (!$flag) {
            \DB::rollback();
            return $this->error(103);
        }
        Models\Record::create([
            'lesson_id' => $lessonId,
            'user_id' => $curUserObj->id,
            'is_trial' => $isTrial,
            'status' => 1,
        ]);
        \DB::commit();

        return $this->output(['lessonId' => $lessonObj->id]);
    }
}
