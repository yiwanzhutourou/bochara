<?php

namespace App\Models;

/**
 * App\Models\MSmsCode
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed user_id
 * @property mixed mobile
 * @property mixed code
 * @property mixed create_time
 */
class MSmsCode extends \Eloquent {

    // table name
    protected $table = 'bocha_sms_code';

    public $timestamps = false;
}