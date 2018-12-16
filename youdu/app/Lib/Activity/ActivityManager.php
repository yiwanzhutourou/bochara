<?php

namespace App\Lib\Activity;

use App\Lib\Douban\DoubanManager;
use App\Lib\HttpClient\HttpClient;
use App\Models\MArticle;
use App\Models\MBook;
use App\Models\MCard;
use App\Utils\CommonUtils;

// TODO 搞个运营后台
class ActivityManager {

    const BOCHA_ACTIVITY_USER_ID = 1;

    const ACTIVITY_CARD_ID = 1;
    const ACTIVITY_CARD_TITLE = '';
    const ACTIVITY_CARD_DETAIL_TITLE = '';
    const ACTIVITY_CARD_CONTENT = '';
    const ACTIVITY_CARD_PIC = 'http://pic.youdushufang.com/zhonshanhuodong2.jpeg';
    const ACTIVITY_CARD_BANNER_PIC = 'http://pic.youdushufang.com/zhongshanhuodong1.png?imageView2/0/format/jpg/q/75|imageslim';

    const ACTIVITY_CARD_ID_2 = 2;
    const ACTIVITY_CARD_TITLE_2 = '';
    const ACTIVITY_CARD_DETAIL_TITLE_2 = '';
    const ACTIVITY_CARD_CONTENT_2 = '';
    const ACTIVITY_CARD_PIC_2 = 'http://pic.youdushufang.com/gongyue2.jpeg?imageView2/0/format/jpg/q/75|imageslim';
    const ACTIVITY_CARD_BANNER_PIC_2 = 'http://pic.youdushufang.com/gongyue1.jpeg?imageView2/0/format/jpg/q/75|imageslim';

    public static function createActivityItemOnlyPic() {
        return [
            'type' => 'card',
            'data' => [
                'id'            => self::ACTIVITY_CARD_ID,
                'title'         => self::ACTIVITY_CARD_TITLE,
                'picUrl'        => self::ACTIVITY_CARD_BANNER_PIC,
                'fullPic'       => self::ACTIVITY_CARD_PIC,
            ],
        ];
    }

    public static function createActivityItemOnlyPic2() {
        return [
            'type' => 'card',
            'data' => [
                'id'            => self::ACTIVITY_CARD_ID_2,
                'title'         => self::ACTIVITY_CARD_TITLE_2,
                'picUrl'        => self::ACTIVITY_CARD_BANNER_PIC_2,
                'fullPic'       => self::ACTIVITY_CARD_PIC_2,
            ],
        ];
    }

    public static function createActivityItem() {
        return [
            'type' => 'card',
            'data' => [
                'id'            => self::ACTIVITY_CARD_ID,
                'title'         => self::ACTIVITY_CARD_TITLE,
                'picUrl'        => self::ACTIVITY_CARD_BANNER_PIC,
            ],
        ];
    }

    public static function createActivityItem2() {
        return [
            'type' => 'card',
            'data' => [
                'id'            => self::ACTIVITY_CARD_ID_2,
                'title'         => self::ACTIVITY_CARD_TITLE_2,
                'picUrl'        => self::ACTIVITY_CARD_BANNER_PIC_2,
            ],
        ];
    }

    public static function createCardItem($id) {
        $card = MCard::find($id);
        if (!$card) {
            return [];
        }
        return [
            'type' => 'card',
            'data' => [
                'id'            => $id,
                'title'         => $card->title,
                'picUrl'        => $card->pic_url,
            ],
        ];
    }

    public static function createArticleItem($id) {
        $article = MArticle::find($id);
        if (!$article) {
            return [];
        }
        return [
            'type' => 'article',
            'data' => [
                'id'            => $id,
                'title'         => $article->title,
                'picUrl'        => $article->pic_url,
            ],
        ];
    }

    public static function createBookItem($isbn, $bookPic, $label = '图书推荐') {
        $bochaBook = MBook::find($isbn);
        if ($bochaBook) {
            return [
                'type' => 'book',
                'data' => [
                    'id'     => $bochaBook->isbn,
                    'title'  => "{$label}: {$bochaBook->title}",
                    'picUrl' => $bookPic,
                ],
            ];
        }

        $url = "https://api.douban.com/v2/book/{$isbn}";
        $response = HttpClient::get($url);
        $doubanBook = json_decode($response);
        if ($doubanBook === null || empty($doubanBook->id)) {
            return null;
        }

        $book = DoubanManager::copy($doubanBook);
        return [
            'type' => 'book',
            'data' => [
                'id'     => $book->isbn,
                'title'  => "{$label}: {$book->title}",
                'picUrl' => $bookPic,
            ],
        ];
    }

    public static function createNewBookItem($isbn, $bookPic) {
        return self::createBookItem($isbn, $bookPic, '新书推荐');
    }

    public static function createActivityDetail() {
        return [
            'id'            => self::ACTIVITY_CARD_ID,
            'user'          => [
                'id'       => self::BOCHA_ACTIVITY_USER_ID,
                'nickname' => '有读书房',
                'avatar'   => 'http://pic.youdushufang.com/Fm3qYpsmNFGRDbWeTOQDRDfiJz9l?imageView2/1/w/640/h/640/format/jpg/q/75|imageslim',
            ],
            'title'         => self::ACTIVITY_CARD_DETAIL_TITLE,
            'content'       => self::ACTIVITY_CARD_CONTENT,
            'picUrl'        => CommonUtils::getOriginalImgUrl(self::ACTIVITY_CARD_PIC),
            'book'          => null,
            'isMe'          => false,
            'showBottom'    => false,
        ];
    }

    public static function createActivityDetail2() {
        return [
            'id'            => self::ACTIVITY_CARD_ID_2,
            'user'          => [
                'id'       => self::BOCHA_ACTIVITY_USER_ID,
                'nickname' => '有读书房',
                'avatar'   => 'http://pic.youdushufang.com/Fm3qYpsmNFGRDbWeTOQDRDfiJz9l?imageView2/1/w/640/h/640/format/jpg/q/75|imageslim',
            ],
            'title'         => self::ACTIVITY_CARD_DETAIL_TITLE_2,
            'content'       => self::ACTIVITY_CARD_CONTENT_2,
            'picUrl'        => CommonUtils::getOriginalImgUrl(self::ACTIVITY_CARD_PIC_2),
            'book'          => null,
            'isMe'          => false,
            'showBottom'    => false,
        ];
    }
}