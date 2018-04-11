<?php

namespace App\Models;

/**
 * App\Models\MFollow
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed from_id
 * @property mixed to_id
 * @property mixed create_time
 */
class MFollow extends \Eloquent {

    // table name
    protected $table = 'bocha_follow';

    public $timestamps = false;
}