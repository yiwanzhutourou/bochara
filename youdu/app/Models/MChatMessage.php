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

    // table name
    protected $table = 'bocha_chat_message';

    public $timestamps = false;
}