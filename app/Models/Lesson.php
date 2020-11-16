<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $table = 'bmt_lesson';
    protected $guarded = [];

    public static function calcCourseLesson($courseIds) {
        $lessonColl = Lesson::whereIn('course_id', $courseIds)->get();
        $courseDict = [];
        foreach ($lessonColl as $lessonObj) {
            if (!@$courseDict[$lessonObj->course_id]) {
                $courseDict[$lessonObj->course_id] = [
                    'totalLesson' => 0,
                    'pendingLesson' => 0,
                ];
            }
            $courseDict[$lessonObj->course_id]['totalLesson']++;
            if (strtotime($lessonObj->start_time) > time()) {
                $courseDict[$lessonObj->course_id]['pendingLesson']++;
            }
        }

        return $courseDict;
    }
}
