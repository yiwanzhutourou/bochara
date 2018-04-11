<?php

namespace App\Models;

/**
 * App\Models\MArticle
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed author_id
 * @property mixed title
 * @property mixed content
 * @property mixed pic_url
 * @property mixed create_time
 * @property mixed status
 * @property mixed read_count
 * @property mixed summary
 * @property-read MAuthor author
 */
class MArticle extends \Eloquent {

    const CARD_STATUS_NORMAL = 0;
    const CARD_STATUS_DELETED = 1;

    // table name
    protected $table = 'bocha_article';

    public $timestamps = false;

    public function author() {
        return $this->belongsTo(MAuthor::class);
    }
}