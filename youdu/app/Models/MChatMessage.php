<?php

namespace App\Models;

/**
 * App\Models\MChatMessage
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed user_1
 * @property mixed user_2
 * @property mixed msg_content
 * @property mixed msg_type
 * @property mixed status_1
 * @property mixed status_2
 * @property mixed timestamp
 * @property mixed extra
 */
class MChatMessage extends \Eloquent {

    const MSG_STATUS_NORMAL = 0;
    const MSG_STATUS_DELETED = 1;

    const MSG_TYPE_TEXT = 0;
    const MSG_TYPE_BORROW = 1;
    const MSG_TYPE_CONTACT = 2;
    const MSG_TYPE_SYSTEM = 3;

    // table name
    protected $table = 'bocha_chat_message';

    public $timestamps = false;

    protected $fillable = [
        'user_1', 'user_2', 'msg_content', 'msg_type',
        'status_1', 'status_2', 'timestamp', 'extra',
    ];
}