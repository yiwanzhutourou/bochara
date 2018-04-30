<?php

namespace App\Models;

/**
 * App\Models\MBorrowRequest
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed from_user
 * @property mixed to_user
 * @property mixed book_isbn
 * @property mixed create_time
 * @property mixed status
 */
class MBorrowRequest extends \Eloquent {

    const BORROW_STATUS_NORMAL = 0;
    const BORROW_STATUS_ACCEPTED = 1;
    const BORROW_STATUS_DECLINED = 2;
    const BORROW_STATUS_RETURNED = 3;

    // table name
    protected $table = 'bocha_borrow_request';

    public $timestamps = false;

    protected $fillable = [
        'from_user', 'to_user',
        'book_isbn', 'create_time', 'status',
    ];
}