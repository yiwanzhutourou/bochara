<?php

namespace App\Models;

/**
 * App\Models\MUserAddress
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed user_id
 * @property mixed address
 * @property mixed detail
 * @property mixed longitude
 * @property mixed latitude
 * @property mixed city
 */
class MUserAddress extends \Eloquent {
    // table name
    protected $table = 'bocha_user_address';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'address', 'detail',
        'longitude', 'latitude', 'city',
    ];
}