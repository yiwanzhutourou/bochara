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
 * @property-read MUser user
 * @property-read MBook book
 * @property-read MCard card
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

    public function user() {
        return $this->belongsTo(MUser::class, 'user_id');
    }

    public function book() {
        return $this->belongsTo(MBook::class, 'content_id');
    }

    public function card() {
        return $this->belongsTo(MCard::class, 'content_id');
    }

    public static function flow($cursor, $isTop, $count = 40) {
        $queryBuild = MDiscoverFlow::where(['status' => self::DISCOVER_ITEM_APPROVED]);
        if (!$isTop) {
            $cursor = intval($cursor);
            if ($cursor < 0) {
                $cursor = 0;
            }
            $queryBuild->where('create_time', '<', $cursor);
        }
        return $queryBuild->orderByDesc('create_time')
            ->take($count);
    }
}