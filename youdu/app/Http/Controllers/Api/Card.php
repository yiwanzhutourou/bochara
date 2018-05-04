<?php

namespace App\Http\Controllers\Api;

use App\Formatters\BookFormatter;
use App\Formatters\CardFormatter;
use App\Http\Controllers\Api\Exceptions\Exception;
use App\Http\Controllers\Api\Lib\Visitor;
use App\Lib\Activity\ActivityManager;
use App\Lib\Discover\DiscoverManager;
use App\Lib\Douban\DoubanManager;
use App\Lib\Pulp\PulpManager;
use App\Models\MBook;
use App\Models\MCard;
use App\Models\MCardApproval;
use App\Models\MDiscoverFlow;
use App\Models\MUser;
use App\Models\MUserBook;
use App\Repositories\ChatRepository;
use App\Repositories\UserRepository;
use App\Utils\CommonUtils;
use App\Utils\JsonUtils;

class Card extends ApiBase {

    /**
     * @param $content
     * @param $title
     * @param $picUrl
     * @param string $book
     * @return mixed
     * @throws Exception
     */
    public function insertNew($content, $title, $picUrl, $book = '') {
        $user = Visitor::instance()->checkAuth();

        // 客户端传过来的豆瓣 Book 对象
        $bookIsbn = '';
        if (!empty($book)) {
            $doubanBook = json_decode($book);
            if ($doubanBook !== null && !empty($doubanBook->id)) {
                $bochaBook = new MBook();
                DoubanManager::copy($bochaBook, $doubanBook);
                $bochaBook->save();
                $bookIsbn = $bochaBook->isbn;
            }
        }

        $userId = $user->id;

        if (PulpManager::checkPulp($picUrl, [
            'user_id' => $userId,
            'title'   => $title,
            'content' => $content,
        ])) {
            throw new Exception(Exception::RESOURCE_IS_PULP, '你的图片不符合规范，不可以在有读书房使用');
        }

        $card = MCard::create([
            'user_id'     => $userId,
            'title'       => $title,
            'content'     => $content,
            'pic_url'     => $picUrl,
            'book_isbn'   => $bookIsbn,
            'create_time' => time(),
            'status'      => MCard::CARD_STATUS_NORMAL,
            'read_count'  => 0,
        ]);

        if ($card) {
            // 将卡片加入发现流的待审核状态
            DiscoverManager::addNewCardToDiscoverFlow($card);
            // 给管理员发一条消息提醒审核(=.=目前就是给自己人发一下系统消息,等管理后台做出来再下掉)
            ChatRepository::sendNewPostMessage($card, $user->nickname);
            return $card->id;
        }

        throw new Exception(Exception::INTERNAL_ERROR , '操作失败请稍后重试~');
    }

    /**
     * @param $cardId
     * @param $content
     * @param $title
     * @param $picUrl
     * @param $picModified
     * @return string
     * @throws Exception
     */
    public function modify($cardId, $content, $title, $picUrl, $picModified) {
        $user = Visitor::instance()->checkAuth();
        $userId = $user->id;
        if (PulpManager::checkPulp($picUrl, [
            'user_id' => $userId,
            'title'   => $title,
            'content' => $content,
        ])) {
            throw new Exception(Exception::RESOURCE_IS_PULP, '你的图片不符合规范，不可以在有读书房使用');
        }

        $card = MCard::find($cardId);
        if (!$card) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '卡片不存在');
        }

        $card->title = $title;
        $card->content = $content;
        if ($picModified) {
            $card->pic_url = $picUrl;
        }
        $card->update();

        // 将卡片重新加入发现流的待审核状态
        DiscoverManager::resetCardStatusInDiscoverFlow($cardId, $userId);
        // 给管理员发一条消息提醒审核(=.=目前就是给自己人发一下系统消息,等管理后台做出来再下掉)
        ChatRepository::sendNewPostMessage($card, $user->nickname);

        return 'ok';
    }

    /**
     * @param $cardId
     * @return string
     * @throws Exception
     */
    public function delete($cardId) {
        $user = Visitor::instance()->checkAuth();
        $card = MCard::find($cardId);
        if (!$card || $card->status === MCard::CARD_STATUS_DELETED
                || $card->user_id !== $user->id) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '读书卡片不存在~');
        }
        $card->status = MCard::CARD_STATUS_DELETED;
        // 将卡片从发现流中删除
        DiscoverManager::removeCardFromDiscoverFlow($cardId, $user->id);
        return 'ok';
    }

    /**
     * @param $cardId
     * @return array
     * @throws Exception
     */
    public function approve($cardId) {
        $user = Visitor::instance()->checkAuth();
        $card = MCard::find($cardId);
        if (!$card || $card->status === MCard::CARD_STATUS_DELETED) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '读书卡片不存在~');
        }
        $userId = $user->id;

        $approval = MCardApproval::where([
            'user_id' => $userId,
            'card_id' => $cardId,
        ])->first();

        // 已经点过赞的就让他去吧
        if (!$approval) {
            MCardApproval::create([
                'user_id'     => $userId,
                'card_id'     => $cardId,
                'user_avatar' => $user->avatar,
                'create_time' => time(),
            ]);

            $extra = [
                'router' => 'card',
                'extra'  => $cardId,
            ];

            if (intval($userId) !== intval($card->user_id)) {
                // 给被点赞的同志发一条系统消息
                ChatRepository::sendSystemMessage(ChatRepository::BOCHA_SYSTEM_USER_ID,
                    $card->user_id,
                    "书友 {$user->nickname} 给你的读书卡片 {$card->title} 点了一个赞~",
                    JsonUtils::json_stringify($extra));
            }
        }

        return [
            'result' => 'ok',
            'id'     => $user->id,
            'avatar' => $user->avatar,
        ];
    }

    /**
     * @param $cardId
     * @return array
     * @throws Exception
     */
    public function unapprove($cardId) {
        $user = Visitor::instance()->checkAuth();
        $card = MCard::find($cardId);
        if (!$card || $card->status === MCard::CARD_STATUS_DELETED) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '读书卡片不存在~');
        }
        $userId = $user->id;
        try {
            MCardApproval::create([
                'card_id' => $cardId,
                'user_id' => $userId,
            ])->delete();
        } catch (\Exception $e) {
            throw new Exception(Exception::INTERNAL_ERROR , '操作失败请稍后重试~');
        }
        return [
            'result' => 'ok',
            'id'     => $user->id,
            'avatar' => $user->avatar,
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getMyCards() {
        $user = Visitor::instance()->checkAuth();
        return $this->getCardsByUser($user);
    }

    /**
     * @param $cardId
     * @return array
     * @throws Exception
     */
    public function getCardById($cardId) {
        $card = MCard::find($cardId);
        if (!$card || $card->status === MCard::CARD_STATUS_DELETED) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '读书卡片不存在~');
        }

        $cardUser = $card->user;
        $book = $card->book;
        $approvals = $card->approvals()
            ->orderByDesc('create_time')
            ->get();

        // has approved
        $hasApproved = false;
        $user = Visitor::instance()->getUser();
        if ($user) {
            $hasApproved = MCardApproval::where('user_id', '=', $user->id)
                ->where('card_id', '=', $card->id)
                ->count() > 0;
        }

        // 增加一次浏览
        MCard::where('id', '=', $card->id)
            ->increment('read_count');
        return CardFormatter::detail($card, $cardUser,
            $book, $approvals, $hasApproved);
    }

    /**
     * @param $userId
     * @return array
     * @throws Exception
     */
    public function getUserCards($userId) {
        $user = MUser::find($userId);
        if (!$user) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND , '用户不存在~');
        }
        return $this->getCardsByUser($user);
    }

    private function getCardsByUser(MUser $user) {
        $cards = $user->cards()->get();
        $result = [];
        /** @var MCard $card */
        foreach ($cards as $card) {
            $result[] = [
                'id'            => $card->id,
                'title'         => $card->title,
                'content'       => mb_substr($card->content, 0, 48, 'utf-8'),
                'picUrl'        => CommonUtils::getListThumbnailUrl($card->pic_url),
                'createTime'    => $card->create_time,
                'readCount'     => $card->read_count,
                'approvalCount' => $card->approvalCount(),
            ];
        }
        return $result;
    }

    public function getBookPageData($isbn, $latitude = 31.181471, $longitude = 121.438378) {
        $cardList = $this->getBookCards($isbn);

        $userBooks = MUserBook::where(['isbn' => $isbn])->get();
        $userList = [];
        foreach ($userBooks as $userBook) {
            $user = MUser::find($userBook->user_id);
            if (!$user) {
                continue;
            }
            $addresses = UserRepository::getUserAddresses(
                $user, $latitude, $longitude, true);
            $userAddress = array_values($addresses)[0];
            $distanceText = Visitor::instance()->isMe($userBook->user_id) ? ''
                : (empty($userAddress) ? '' : CommonUtils::getDistanceString($userAddress['distance']));
            $userList[] = [
                'id'           => $user->id,
                'nickname'     => $user->nickname,
                'avatar'       => $user->avatar,
                'address'      => $userAddress,
                'distanceText' => $distanceText,
            ];
        }
        // sort: 距离升序排列
        usort($userList, function($a, $b) {
            if (empty($a['address'])) {
                return 1;
            }
            if (empty($b['address'])) {
                return -1;
            }
            return ($a['address']['distance'] > $b['address']['distance']) ? 1 : -1;
        });

        return [
            'users'   => $userList,
            'cards'   => $cardList,
            'hasBook' => Visitor::instance()->hasBook($isbn) ? 1 : 0,
        ];
    }

    public function getBookCards($isbn, $page = 0, $count = 5) {
        $cards = MCard::where(['status' => MCard::CARD_STATUS_NORMAL, 'book_isbn' => $isbn])
            ->orderByDesc('create_time')
            ->skip($page * $count)
            ->take($count)
            ->get();

        $formattedCards = [];
        foreach ($cards as $card) {
            /** @var MUser $user */
            $user = $card->user;
            if (!$user) {
                continue;
            }
            $formattedCards[] = [
                'id'            => $card->id,
                'user'          => [
                    'id'        => $user->id,
                    'nickname'  => $user->nickname,
                    'avatar'    => $user->avatar,
                ],
                'title'         => $card->title,
                'content'       => mb_substr($card->content, 0, 48, 'utf-8'),
                'picUrl'        => CommonUtils::getListThumbnailUrl($card->pic_url),
                'createTime'    => $card->create_time,
                'readCount'     => $card->read_count,
                'approvalCount' => $card->approvalCount(),
            ];
        }
        return $formattedCards;
    }

    /*
     * 第一版出去最简单的卡片流:读书卡片和最新图书混排的流
     * $cursor 卡片列表的时间戳
     * $bookCursor 书列表的时间戳
     * $isUp 下拉或者上拉刷新
     */
    public function getDiscoverPageData($cursor, $isTop) {
        $discoverList = MDiscoverFlow::flow($cursor, $isTop)->get();
        $resultList = [];
        /** @var MDiscoverFlow $item */
        foreach ($discoverList as $item) {
            if ($item->type === 'card') {
                $user = $item->user;
                $card = $item->card;
                if (!$card && $card->status == MCard::CARD_STATUS_NORMAL) {
                    $resultList[] = [
                        'type' => 'card',
                        'data' => [
                            'id'            => $card->id,
                            'user'          => [
                                'id'        => $user->id,
                                'nickname'  => $user->nickname,
                                'avatar'    => $user->avatar,
                            ],
                            'title'         => $card->title,
                            'content'       => mb_substr($card->content, 0, 48, 'utf-8'),
                            'picUrl'        => CommonUtils::getListThumbnailUrl($card->pic_url),
                            'createTime'    => $card->create_time,
                            'readCount'     => intval($card->read_count),
                            'approvalCount' => $card->approvalCount(),
                        ],
                    ];
                }
            } else if ($item->type === 'book') {
                $user = $item->user;
                $book = $item->book;
                // TODO 图书去重
                $duplicate = false;
                foreach($resultList as $k => $v){
                    if ($v['type'] === 'book'
                        && $v['data']['isbn'] === $book->isbn){
                        $duplicate = true;
                        break;
                    }
                }
                if (!$duplicate) {
                    $resultList[] = [
                        'type' => 'book',
                        'data' => [
                            'isbn'         => $book->isbn,
                            'user'       => [
                                'id'       => $user->id,
                                'nickname' => $user->nickname,
                                'avatar'   => $user->avatar,
                            ],
                            'title'      => $book->title,
                            'author'     => BookFormatter::parseAuthor($book->author),
                            'cover'      => $book->cover,
                            'publisher'  => $book->publisher,
                            'summary'    => mb_substr($book->summary, 0, 96, 'utf-8'),
                            'createTime' => $item->create_time,
                        ],
                    ];
                }
            }
        }

        $topCursor = self::getCursor($resultList, true);
        $bottomCursor = self::getCursor($resultList, false);

        $banners = [];
        if ($isTop) {
            // 活动置顶
            $banners[] = ActivityManager::createActivityItemOnlyPic2();
            $banners[] = ActivityManager::createActivityItemOnlyPic();
            $acBook = ActivityManager::createNewBookItem('27199470', 'https://img01.yit.com/media/3c1f48ed-9032-40d3-87dd-12eee304d675.jpg');
            if ($acBook !== false) {
                $banners[] = $acBook;
            }
        }

        return [
            'banner'           => $banners,
            'list'             => $resultList,
            'topCursor'        => $topCursor,
            'bottomCursor'     => $bottomCursor,
            'bookTopCursor'    => -1, // 结构改了，暂时没有用了
            'bookBottomCursor' => -1,
            'showPost'         => true,
        ];
    }

    private static function getCursor($list, $isTop) {
        if (count($list) > 0) {
            $index = $isTop ? 0 : (count($list) - 1);
            $item = array_values($list)[$index];
            return $item['data']['createTime'];
        }
        return -1;
    }
}