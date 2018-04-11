<?php

namespace App\Models;

/**
 * App\Models\MCard
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed user_id
 * @property mixed title
 * @property mixed content
 * @property mixed pic_url
 * @property mixed book_isbn
 * @property mixed create_time
 * @property mixed status
 * @property mixed read_count
 * @property-read MUser user
 * @property-read MBook book
 */
class MCard extends \Eloquent {

    const CARD_STATUS_NORMAL = 0;
    const CARD_STATUS_DELETED = 1;

    // table name
    protected $table = 'bocha_book_card';

    public $timestamps = false;

    public function user() {
        return $this->belongsTo(MUser::class);
    }

    public function book() {
        return $this->belongsTo(MBook::class);
    }

    public function approvals() {
        return $this->hasMany(MCardApproval::class, 'card_id');
    }
}
