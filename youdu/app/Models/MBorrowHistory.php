<?php

namespace App\Models;

/**
 * App\Models\MBorrowHistory
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed from_user
 * @property mixed to_user
 * @property mixed book_isbn
 * @property mixed book_title
 * @property mixed book_cover
 * @property mixed date
 * @property mixed status
 * @property mixed form_id
 */
class MBorrowHistory extends \Eloquent {

    const BORROW_STATUS_INIT = 0;
    const BORROW_STATUS_AGREED = 1;
    const BORROW_STATUS_DECLINED = 2;
    const BORROW_STATUS_DISMISSED = 3;

    // table name
    protected $table = 'bocha_borrow_history';

    public $timestamps = false;
}