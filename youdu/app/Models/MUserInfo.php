<?php

namespace App\Models;

/**
 * App\Models\MUserInfo
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed user_id
 * @property mixed info
 */
class MUserInfo extends \Eloquent {
    // table name
    protected $table = 'bocha_info';

    public $timestamps = false;
}