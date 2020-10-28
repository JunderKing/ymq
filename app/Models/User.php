<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'bmt_user';
    protected $guarded = [];

    public static $curUserId;
    public static $curUserObj;
}
