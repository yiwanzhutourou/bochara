<?php

namespace App\Repositories;

use App\Models\MUser;

class UserRepository {

    /**
     * @param mixed $wxOpenId
     * @return MUser|null
     */
    public static function findByWxOpenId($wxOpenId) {
        return MUser::where(['wechat_open_id' => $wxOpenId])->first();
    }

    /**
     * TODO for test use
     *
     * @return MUser|null
     */
    public static function testUser() {
        return MUser::find(34);
    }
}