<?php

namespace App\Models;

/**
 * App\Models\MBorrowRequest
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed from
 * @property mixed to
 * @property mixed book_isbn
 * @property mixed create_time
 * @property mixed status
 */
class MBorrowRequest extends \Eloquent {

    // table name
    protected $table = 'bocha_borrow_request';

    public $timestamps = false;
}