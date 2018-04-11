<?php

namespace App\Models;

/**
 * App\Models\MXu
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed name
 * @property mixed value
 * @property mixed create_time
 * @property mixed expire_time
 */
class MXu extends \Eloquent {

    // table name
    protected $table = 'bocha_xu';

    public $timestamps = false;
}