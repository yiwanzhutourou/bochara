<?php

namespace App\Models;

/**
 * App\Models\MDiscoverFlow
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed type
 * @property mixed content_id
 * @property mixed user_id
 * @property mixed status
 * @property mixed create_time
 */
class MDiscoverFlow extends \Eloquent {

    const DISCOVER_ITEM_NEW = 0;
    const DISCOVER_ITEM_APPROVED = 1;
    const DISCOVER_ITEM_DENIED = 2;
    const DISCOVER_ITEM_USER_DELETED = 3;

    // table name
    protected $table = 'bocha_discover_flow';

    public $timestamps = false;

    protected $fillable = ['type', 'content_id', 'user_id', 'status', 'create_time'];
}