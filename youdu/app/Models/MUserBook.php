<?php

namespace App\Models;

/**
 * App\Models\MUserBook
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed user_id
 * @property mixed isbn
 * @property mixed create_time
 * @property mixed can_be_borrowed
 * @property mixed total_count
 * @property mixed left_count
 */
class MUserBook extends \Eloquent {
    // table name
    protected $table = 'bocha_user_book';

    public $timestamps = false;
}