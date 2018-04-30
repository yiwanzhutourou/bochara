<?php

namespace App\Formatters;

use App\Models\MBook;
use App\Models\MCard;
use App\Models\MUser;
use App\Utils\CommonUtils;

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
        return [];
    }

    public static function cardList($cards) {
        $formattedCards = [];
        if ($cards) {
            /** @var MCard $card */
            foreach ($cards as $card) {
                if ($card->book_isbn) {
                    $book = MBook::find($card->book_isbn);
                }
                $formattedCards[] = [
                    'id'         => $card->id,
                    'title'      => $card->title,
                    'content'    => mb_substr($card->content, 0, 48, 'utf-8'),
                    'picUrl'     => CommonUtils::getListThumbnailUrl($card->pic_url),
                    'bookTitle'  => $book->title ?? '',
                    'createTime' => $card->create_time,
                ];
            }
        }
        return $formattedCards;
    }
}