<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Lib\Visitor;
use App\Models\MBook;
use App\Models\MUser;
use App\Models\MUserBook;
use App\Repositories\UserRepository;
use App\Utils\CommonUtils;

class Search extends ApiBase {

    public function books($keyword, $longitude = 0, $latitude = 0,
                          $count = 100, $page = 0) {
        if (empty($keyword)) {
            return [];
        }
        $books = MBook::where('title', 'like', "%{$keyword}%")
            ->orWhere('author', 'like', "%{$keyword}%")
            ->orWhere('publisher', 'like', "%{$keyword}%")
            ->skip($page * $count)
            ->take($count)
            ->get();
        // TODO 改用数据库 join
        $result = [];
        foreach ($books as $book) {
            /** @var MBook $book */
            $userBooks = MUserBook::where(['isbn' => $book->isbn])
                ->get();
            $users = [];
            foreach ($userBooks as $userBook) {
                $userId = $userBook->user_id;
                $user = MUser::find($userId);
                $addresses = UserRepository::getUserAddresses($user, $longitude, $latitude);
                $distanceText = '';
                $userAddress = [];
                if (!empty($addresses)) {
                    $userAddress = $addresses[0];
                    $distanceText = Visitor::instance()->isMe($user->id)
                        ? ''
                        : CommonUtils::getDistanceString($userAddress['distance']);
                }
                $users[] = [
                    'id' => $user->id,
                    'nickname' => $user->nickname,
                    'avatar' => $user->avatar,
                    'address' => $userAddress,
                    'distanceText' => $distanceText,
                ];
            }
            $result[] = [
                'book' => [
                    'isbn' => $book->isbn,
                    'title' => $book->title,
                    'author' => self::parseAuthor($book->author),
                    'cover' => $book->cover,
                    'publisher' => $book->publisher,
                ],
                'users' => $users
            ];
        }

        // filter:没有user的不返回
        $result = array_filter($result, function($item) {
            return !empty($item['users']);
        });

        // sort: 按照有这本书的书房数量排序
        usort($result, function($a, $b) {
            return (count($a['users']) < count($b['users'])) ? 1 : -1;
        });

        return $result;
    }

    public function users($keyword, $longitude = 0, $latitude = 0,
                          $count = 100, $page = 0) {
        if (empty($keyword)) {
            return [];
        }
        $users = MUser::where('nickname', 'like', "%{$keyword}%")
            ->skip($page * $count)
            ->take($count)
            ->get();
        // TODO 改用数据库 join
        $result = [];
        foreach ($users as $user) {
            $addresses = UserRepository::getUserAddresses($user, $longitude, $latitude);
            $distanceText = '';
            $userAddress = [];
            if (!empty($addresses)) {
                $userAddress = $addresses[0];
                $distanceText = Visitor::instance()->isMe($user->id)
                    ? ''
                    : CommonUtils::getDistanceString($userAddress['distance']);
            }
            $result[] = [
                'id'          => $user->id,
                'nickname'    => $user->nickname,
                'avatar'      => $user->avatar,
                'address'     => $userAddress,
                'bookCount'   => $user->bookCount(),
                'distanceText' => $distanceText,
            ];
        }
        return $result;
    }

    private static function parseAuthor($authorString) {
        if (empty($authorString)) {
            return '';
        }

        $authors = json_decode($authorString);
        $result = '';
        foreach ($authors as $author) {
            $result .= ($author . ' ');
        }
        if (strlen($result) > 0) {
            $result = substr($result, 0, strlen($result) - 1);
        }
        return $result;
    }
}