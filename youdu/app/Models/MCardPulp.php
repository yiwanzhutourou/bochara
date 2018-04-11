<?php

namespace App\Models;

/**
 * App\Models\MCardPulp
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed user_id
 * @property mixed title
 * @property mixed content
 * @property mixed pic_url
 * @property mixed create_time
 * @property mixed pulp_rate
 * @property mixed pulp_label
 * @property mixed pulp_review
 */
class MCardPulp extends \Eloquent {

    // table name
    protected $table = 'bocha_book';

    public $timestamps = false;
}