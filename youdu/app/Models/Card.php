<?php

namespace App\Models;

/**
 * App\Models\Card
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed userId
 * @property mixed title
 * @property mixed content
 * @property mixed picUrl
 * @property mixed bookIsbn
 * @property mixed createTime
 * @property mixed status
 * @property mixed readCount
 */
class Card extends \Eloquent {

    // table name
    protected $table = 'bocha_book_card';

    // primary key
    protected $primaryKey = '_id';
    public $incrementing = false;
}
