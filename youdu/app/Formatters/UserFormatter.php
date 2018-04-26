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

    public static function contact(?MUser $user) {
        if ($user) {
            $contactJson = $user->contact;
            $contact = json_decode($contactJson);
            if (isset($contact->name) && isset($contact->contact)) {
                if (in_array($contact->name, ['微信', 'QQ', '邮箱'])
                    && !empty($contact->contact)) {
                    return [
                        'name'    => $contact->name,
                        'contact' => $contact->contact
                    ];
                }
            }
        }
        return [
            'name'    => '',
            'contact' => ''
        ];
    }
}