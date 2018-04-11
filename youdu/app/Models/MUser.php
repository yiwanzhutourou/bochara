<?php

namespace App\Models;

/**
 * App\Models\MUser
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed token
 * @property mixed open_id
 * @property mixed session
 * @property mixed create_time
 * @property mixed expire_time
 * @property mixed nickname
 * @property mixed avatar
 * @property mixed contact
 * @property mixed mobile
 */
class MUser extends \Eloquent {
    // table name
    protected $table = 'bocha_user';

    public $timestamps = false;
}