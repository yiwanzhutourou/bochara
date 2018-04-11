<?php

namespace App\Formatters;

use App\Models\MUser;

class UserFormatter {

    public static function simple(?MUser $user) {
        if ($user) {
            return [
                'id'       => $user->id,
                'nickname' => $user->nickname,
                'avatar'   => $user->avatar,
            ];
        }

        return [];
    }
}