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

    // table name
    protected $table = 'bocha_discover_flow';

    public $timestamps = false;
}