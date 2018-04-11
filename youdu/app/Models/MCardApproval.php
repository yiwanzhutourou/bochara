<?php

namespace App\Models;

/**
 * App\Models\MCardApproval
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed user_id
 * @property mixed card_id
 * @property mixed create_time
 * @property mixed user_avatar
 */
class MCardApproval extends \Eloquent {

    // table name
    protected $table = 'bocha_book_card_approval';

    public $timestamps = false;
}