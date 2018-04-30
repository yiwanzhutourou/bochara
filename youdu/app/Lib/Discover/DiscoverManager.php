<?php

namespace App\Lib\Discover;

use App\Models\MBook;
use App\Models\MDiscoverFlow;
use App\Models\MUserBook;

class DiscoverManager {

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
