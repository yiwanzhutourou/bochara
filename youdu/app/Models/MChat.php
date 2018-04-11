<?php

namespace App\Models;

/**
 * App\Models\MChat
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed user_1
 * @property mixed user_2
 * @property mixed msg_content
 * @property mixed msg_sender
 * @property mixed msg_type
 * @property mixed status
 * @property mixed timestamp
 * @property mixed extra
 * @property mixed unread_count
 */
class MChat extends \Eloquent {

    // table name
    protected $table = 'bocha_chat';

    public $timestamps = false;
}