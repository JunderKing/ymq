<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $table = 'bmt_activity';
    protected $guarded = [];

    // 1 进行中，2 已截止，3 已结束，4 已取消
    public static function getStatusText($activityObj) {
        $deadlineTs = strtotime($activityObj->deadline);
        $endTs = strtotime($activityObj->end_time);
        $curTs = time();
        $statusText = '';
        if ($activityObj->status == 0) {
            $statusText = '已取消';
        } else if ($curTs > $endTs) {
            $statusText = '已结束';
        } else if ($curTs > $deadlineTs) {
            $statusText = '已截止';
        } else {
            $statusText = '报名中';
        }

        return $statusText;
    }
}
