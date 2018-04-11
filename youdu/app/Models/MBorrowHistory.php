<?php

namespace App\Models;

/**
 * App\Models\MBorrowHistory
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed from
 * @property mixed to
 * @property mixed book_isbn
 * @property mixed book_title
 * @property mixed book_cover
 * @property mixed date
 * @property mixed request_status
 * @property mixed formId
 */
class MBorrowHistory extends \Eloquent {

    // table name
    protected $table = 'bocha_borrow_history';

    public $timestamps = false;
}