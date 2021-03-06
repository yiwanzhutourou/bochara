<?php /** @noinspection PhpUndefinedClassInspection */

namespace App\Models;
use Illuminate\Support\Facades\DB;

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

    public function info() {
        return $this->hasOne(MUserInfo::class, 'user_id');
    }

    public function addresses() {
        return $this->hasMany(MUserAddress::class, 'user_id')
            ->orderBy('id', 'desc');
    }

    public function books() {
        return $this->hasMany(MUserBook::class, 'user_id')
            ->leftJoin('bocha_book',
                'bocha_user_book.isbn', '=', 'bocha_book.isbn')
            ->orderBy('create_time', 'desc');
    }

    public function borrowBooks() {
        return $this->hasMany(MUserBook::class, 'user_id')
            ->where(['can_be_borrowed' => MUserBook::BOOK_CAN_BE_BORROWED])
            ->leftJoin('bocha_book',
                'bocha_user_book.isbn', '=', 'bocha_book.isbn')
            ->orderBy('create_time', 'desc');
    }

    public function bookCount() {
        return $this->hasMany(MUserBook::class, 'user_id')->count();
    }

    public function borrowBookCount() {
        return $this->hasMany(MUserBook::class, 'user_id')
            ->where(['can_be_borrowed' => MUserBook::BOOK_CAN_BE_BORROWED])
            ->count();
    }

    public function cards() {
        return $this->hasMany(MCard::class, 'user_id')
            ->where(['status' => MCard::CARD_STATUS_NORMAL])
            ->orderByDesc('create_time');
    }

    public function cardCount() {
        return $this->hasMany(MCard::class, 'user_id')
            ->where(['status' => MCard::CARD_STATUS_NORMAL])->count();
    }

    public function followers() {
        return $this->belongsToMany(MUser::class,
            'bocha_follow', 'to_id', 'from_id')
            // 按 MFollow 的先后顺序排序
            ->withPivot('id')
            ->orderBy('pivot_id', 'desc');
    }

    public function followerCount() {
        return $this->hasMany(MFollow::class, 'to_id')->count();
    }

    public function followings() {
        return $this->belongsToMany(MUser::class,
            'bocha_follow', 'from_id', 'to_id')
            // 按 MFollow 的先后顺序排序
            ->withPivot('id')
            ->orderBy('pivot_id', 'desc');
    }

    public function followingCount() {
        return $this->hasMany(MFollow::class, 'from_id')->count();
    }

    public function borrowHistory() {
        // join 的两张表有重名字段，用 ORM 能写吗？
        return DB::table('bocha_borrow_history')
            ->select([
                'bocha_borrow_history.id as history_id',
                'bocha_borrow_history.to_user',
                'bocha_borrow_history.book_title',
                'bocha_borrow_history.book_cover',
                'bocha_borrow_history.date',
                'bocha_borrow_history.status',
                'bocha_user.nickname',
            ])
            ->where(['from_user' => $this->id])
            ->where('status', '<', 3)
            ->leftJoin('bocha_user',
                'bocha_borrow_history.to_user', '=', 'bocha_user.id')
            ->orderByDesc('history_id');
    }

    public function borrowRequests() {
        // join 的两张表有重名字段，用 ORM 能写吗？
        return DB::table('bocha_borrow_history')
            ->select([
                'bocha_borrow_history.id as history_id',
                'bocha_borrow_history.from_user',
                'bocha_borrow_history.book_title',
                'bocha_borrow_history.book_cover',
                'bocha_borrow_history.date',
                'bocha_borrow_history.status',
                'bocha_user.nickname',
            ])
            ->where(['to_user' => $this->id])
            ->where('status', '<', 3)
            ->leftJoin('bocha_user',
                'bocha_borrow_history.from_user', '=', 'bocha_user.id')
            ->orderByDesc('history_id');
    }

    public function approvedList() {
        return $this->borrowHistory()
            ->where(['status' => MBorrowHistory::BORROW_STATUS_AGREED]);
    }

    public function borrowOrders($out = false) {
        $queryBuilder = DB::table('bocha_borrow_history')
            ->select([
                'bocha_borrow_history.id as history_id',
                'bocha_borrow_history.from_user',
                'bocha_borrow_history.to_user',
                'bocha_borrow_history.date',
                'bocha_borrow_history.status',
                'bocha_user.id as user_id',
                'bocha_user.nickname',
                'bocha_user.avatar',
                'bocha_book.isbn',
                'bocha_book.title',
                'bocha_book.author',
                'bocha_book.cover',
                'bocha_book.publisher',
            ]);
        if ($out) {
            $queryBuilder->where(['from_user' => $this->id])
                ->leftJoin('bocha_user',
                    'bocha_borrow_history.to_user', '=', 'bocha_user.id');
        } else {
            $queryBuilder->where(['to_user' => $this->id])
                ->leftJoin('bocha_user',
                    'bocha_borrow_history.from_user', '=', 'bocha_user.id');
        }
        return $queryBuilder
            ->leftJoin('bocha_book',
                'bocha_borrow_history.book_isbn', '=', 'bocha_book.isbn')
            ->where('status', '<', 3)
            ->orderByDesc('history_id');
    }

    // TODO 历史原因，分了一张新表，有空整理一下这块
    public function newBorrowRequests($status, $out = false) {
        $queryBuilder = DB::table('bocha_borrow_request')
            ->select([
                'bocha_borrow_request.id as request_id',
                'bocha_borrow_request.from_user',
                'bocha_borrow_request.to_user',
                'bocha_borrow_request.create_time',
                'bocha_borrow_request.status',
                'bocha_user.id as user_id',
                'bocha_user.nickname',
                'bocha_user.avatar',
                'bocha_book.isbn',
                'bocha_book.title',
                'bocha_book.author',
                'bocha_book.cover',
                'bocha_book.publisher',
            ]);
        if ($out) {
            $queryBuilder->where(['from_user' => $this->id])
                ->leftJoin('bocha_user',
                    'bocha_borrow_request.to_user', '=', 'bocha_user.id');
        } else {
            $queryBuilder->where(['to_user' => $this->id])
                ->leftJoin('bocha_user',
                    'bocha_borrow_request.from_user', '=', 'bocha_user.id');
        }
        return $queryBuilder
            ->leftJoin('bocha_book',
                'bocha_borrow_request.book_isbn', '=', 'bocha_book.isbn')
            ->where('status', '=', $status)
            ->orderByDesc('request_id');
    }

    public function chats() {
        return $this->hasMany(MChat::class, 'user_1')
            ->where(['status' => MChatMessage::MSG_STATUS_NORMAL])
            ->orderByDesc('timestamp');
    }
}