<?php

namespace App\Lib\Discover;

use App\Models\MBook;
use App\Models\MCard;
use App\Models\MDiscoverFlow;
use App\Models\MUserBook;

class DiscoverManager {

    public static function addNewCardToDiscoverFlow(MCard $card) {
        // 卡片因为后台会审核，所以去重的逻辑可以再审核的时候认为控制，避免出现新的卡片无法显示在流上的情况。
        if ($card->status === MCard::CARD_STATUS_DELETED) {
            return;
        }
        MDiscoverFlow::create([
            'type'        => 'card',
            'content_id'  => $card->id,
            'user_id'     => $card->user_id,
            'status'      => MDiscoverFlow::DISCOVER_ITEM_NEW, // 初始状态，等待审核
            'create_time' => $card->create_time,
        ]);
    }

    // 修改之后的卡片应该重新审核
    public static function resetCardStatusInDiscoverFlow($cardId, $userId) {
        MDiscoverFlow::where([
            'type'       => 'card',
            'content_id' => $cardId,
            'user_id'    => $userId,
        ])->update(['status' => MDiscoverFlow::DISCOVER_ITEM_NEW]);
    }

    public static function removeCardFromDiscoverFlow($cardId, $userId) {
        MDiscoverFlow::where([
            'type'       => 'card',
            'content_id' => $cardId,
            'user_id'    => $userId,
        ])->update(['status' => MDiscoverFlow::DISCOVER_ITEM_USER_DELETED]);
    }

    public static function addNewBookToDiscoverFlow($book, $userBook) {
        /** @var MBook $book */
        if ($book->douban_raters < 20
            || $book->douban_average < 8.0) {
            return;
        }

        /** @var MUserBook $userBook */
        // 这里的逻辑后续还可以调整的=.=
        // 取流上最新的 30 本书，如果这个用户在这么多书里已经有 3 本书了，就不再添加他新增的书了
        $bookList = self::getNewestBooksFromDiscoverFlow(30);
        if ($bookList !== false) {
            $bookCount = 0;
            foreach ($bookList as $item) {
                /** @var MDiscoverFlow $item */
                if ($item->user_id === $userBook->user_id) {
                    $bookCount++;
                }
                if ($bookCount >= 3) {
                    return;
                }
            }
        }

        // 添加图书到发现流
        MDiscoverFlow::create([
            'type'        => 'book',
            'content_id'  => $userBook->isbn,
            'user_id'     => $userBook->user_id,
            'status'      => MDiscoverFlow::DISCOVER_ITEM_APPROVED, // 图书暂时就用豆瓣的评分，不用审核
            'create_time' => $userBook->create_time,
        ]);
    }

    public static function getNewestBooksFromDiscoverFlow($n) {
        return MDiscoverFlow::where([
            'type'   => 'book',
            'status' => 1,
        ])->orderByDesc('create_time')->take($n)->get();
    }
}
