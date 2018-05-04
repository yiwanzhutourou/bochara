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
 * @property mixed count
 * @property mixed left_count
 */
class MUserBook extends \Eloquent {

    const BOOK_CAN_BE_BORROWED = 0;
    const BOOK_CANNOT_BE_BORROWED = 1;

    // table name
    protected $table = 'bocha_user_book';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'isbn', 'create_time',
        'can_be_borrowed', 'count', 'left_count',
    ];
}