<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models;
use App\Utils;

class LessonController extends Controller
{
    public function createOrUpdate()
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
        \DB::beginTransaction();
        $flag = Models\Lesson::where([['id', $lessonId], ['taken_num', '>', 0]])->decrement('taken_num', 1);
        if ($flag) {
            Models\Record::where([['lesson_id', $lessonId], ['user_id', $curUserObj->id]])->delete();
        }
        \DB::commit();

        return $this->output();
    }

    // 1 报名成功 0 取消报名
    public function join()
    {
        $realName = $this->param('realName', 'nullable', null);
        $lessonId = $this->check('lessonId', 'required|int|min:1');
        $curUserObj = Models\User::$curUserObj;

        if (!$curUserObj->realName && $realName) {
            Models\User::where('id', $curUserObj->id)->update(['real_name' => $realName]);
        }
        // 是否已经报过
        $isExist = Models\Record::where([['lesson_id', $lessonId], ['user_id', $curUserObj->id]])->exists();
        if ($isExist) {
            return $this->error(301);
        }
        // 是否还有剩余的位置
        $lessonObj = Models\Lesson::findOrFail($lessonId);
        if ($lessonObj->total_num <= $lessonObj->taken_num) {
            return $this->error(302);
        }

        \DB::beginTransaction();
        $flag = Models\Lesson::where([['id', $lessonId]])->whereRaw('total_num > taken_num')
            ->increment('taken_num', 1);
        if (!$flag) {
            return $this->error(103);
        }
        Models\Record::create([
            'lesson_id' => $lessonId,
            'user_id' => $curUserObj->id,
            'status' => 1,
        ]);
        \DB::commit();

        return $this->output(['lessonId' => $lessonObj->id]);
    }
}
