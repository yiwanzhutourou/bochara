<?php

namespace App\Models;

/**
 * App\Models\MAuthor
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed nickname
 * @property mixed avatar
 */
class MAuthor extends \Eloquent {

    // table name
    protected $table = 'bocha_author';

    public $timestamps = false;
}