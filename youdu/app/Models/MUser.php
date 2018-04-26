<?php

namespace App\Models;

/**
 * App\Models\MUser
 *
 * @mixin \Eloquent
 * @property mixed id
 * @property mixed token
 * @property mixed wechat_open_id
 * @property mixed wechat_session
 * @property mixed create_time
 * @property mixed expire_time
 * @property mixed nickname
 * @property mixed avatar
 * @property mixed contact
 * @property mixed mobile
 */
class MUser extends \Eloquent {
    // table name
    protected $table = 'bocha_user';

    public $timestamps = false;

    public function addresses() {
        return $this->hasMany(MUserAddress::class, 'user_id');
    }

    public function userBooks() {
        return $this->hasMany(MUserBook::class, 'user_id');
    }

    public function cards() {
        return $this->hasMany(MCard::class, 'user_id')
            ->where(['status' => MCard::CARD_STATUS_NORMAL]);
    }

    public function followers() {
        return $this->belongsToMany(MUser::class,
            'bocha_follow', 'to_id', 'from_id');
    }

    public function followerCount() {
        return $this->hasMany(MFollow::class, 'to_id')->count();
    }

    public function followings() {
        return $this->belongsToMany(MUser::class,
            'bocha_follow', 'from_id', 'to_id');
    }

    public function followingCount() {
        return $this->hasMany(MFollow::class, 'from_id')->count();
    }
}