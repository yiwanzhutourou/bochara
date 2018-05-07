<?php

namespace App\Http\Controllers\Api;

use App\Formatters\BookFormatter;
use App\Formatters\UserFormatter;
use App\Http\Controllers\Api\Exceptions\Exception;
use App\Http\Controllers\Api\Lib\Visitor;
use App\Lib\Baidu\BaiduPoiManager;
use App\Lib\Discover\DiscoverManager;
use App\Lib\Douban\DoubanManager;
use App\Lib\Pulp\PulpManager;
use App\Lib\SMS\AliSmsSender;
use App\Lib\Weixin\WxTemplateMessageManager;
use App\Models\MBook;
use App\Models\MBorrowHistory;
use App\Models\MDiscoverFlow;
use App\Models\MFollow;
use App\Models\MSmsCode;
use App\Models\MUser;
use App\Models\MUserAddress;
use App\Models\MUserBook;
use App\Models\MUserInfo;
use App\Repositories\ChatRepository;
use App\Repositories\UserRepository;
use App\Utils\CommonUtils;
use App\Utils\JsonUtils;

class User extends ApiBase {

    // TODO 要验一下
    /**
     * @param mixed $code
     * @param mixed $nickname
     * @param mixed $avatar
     * @return array
     * @throws Exception
     */
    public function login($code, $nickname, $avatar = '') {
        $url = "https://api.weixin.qq.com/sns/jscode2session?" . http_build_query([
                'appid'      => env('WX_APP_ID'),
                'secret'     => env('WX_SECRET'),
                'js_code'    => $code,
                'grant_type' => 'authorization_code'
            ]);

        $response = json_decode(file_get_contents($url));
        if (!empty($response->errcode)) {
            throw new Exception(Exception::WEIXIN_AUTH_FAILED, '微信验证出错:'.$response->errmsg);
        }

        $openId = $response->openid;
        $session = $response->session_key;

        if (!empty($openId) && !empty($session)) {
            $user = UserRepository::findByWxOpenId($openId);
            if ($user && !empty($user->token)) {
                $token = $user->token;
                $user->wechat_open_id = $openId;
                $user->wechat_session = $session;
                // 假的,暂时token不超时
                $user->create_time = strtotime('now');
                $user->expire_time = strtotime('now + 30 days');
                if (empty($user->nickname)) {
                    $user->nickname = $nickname;
                }
                if (empty($user->avatar)) {
                    $user->avatar = $avatar;
                }
                $user->update();
            } else {
                $token = 'bocha' . uniqid('', true);
                $user = new MUser();
                $user->token = $token;
                $user->wechat_open_id = $openId;
                $user->wechat_session = $session;
                // 假的,暂时token不超时
                $user->create_time = strtotime('now');
                $user->expire_time = strtotime('now + 30 days');
                $user->nickname = $nickname;
                $user->avatar = $avatar;
                $user->contact = "";
                $user->mobile = "";
                $user->save();
            }
            return [
                'token'     => $token,
                'hasMobile' => $user && !empty($user->mobile)
            ];
        }

        throw new Exception(Exception::WEIXIN_AUTH_FAILED, '无法获取openid');
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getSettingsData() {
        $user = Visitor::instance()->checkAuth();
        $contactJson = $user->contact;
        $contact = json_decode($contactJson);
        $mobile = $user->mobile;

        $addressList = [];
        $addresses = $user->addresses()->get();
        foreach ($addresses as $address) {
            if ($address) {
                $addressList[] = json_decode($address->city);
            }
        }

        return [
            'contact'     => $contact,
            'mobileTail'  => substr($mobile, strlen($mobile) - 4, strlen($mobile)),
            'address'     => $addressList,
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getMinePageData() {
        $user = Visitor::instance()->checkAuth();
        return [
            'nickname'       => $user->nickname,
            'avatar'         => $user->avatar,
            'bookCount'      => $user->bookCount(),
            'cardCount'      => $user->cards()->count(),
            'followerCount'  => $user->followerCount(),
            'followingCount' => $user->followingCount(),
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getUserContact() {
        $user = Visitor::instance()->checkAuth();
        return UserFormatter::contact($user);
    }

    /**
     * @param $requestId
     * @return array
     * @throws Exception
     */
    public function getUserContactByRequest($requestId) {
        $user = Visitor::instance()->checkAuth();
        $history = MBorrowHistory::find($requestId);
        if ($history) {
            if ($history->from_user !== $user->id) {
                throw new Exception(Exception::BAD_REQUEST, '无法查看~');
            }
            if ($history->status === MBorrowHistory::BORROW_STATUS_AGREED) {
                $bookOwner = MUser::find($history->to_user);
                if ($bookOwner) {
                    return UserFormatter::contact($bookOwner);
                }
                throw new Exception(Exception::RESOURCE_NOT_FOUND, '用户不存在');
            }
            throw new Exception(Exception::BAD_REQUEST, '书房主人还未同意请求~');
        }
        throw new Exception(Exception::RESOURCE_NOT_FOUND, '借书请求不存在');
    }

    /**
     * @param $name
     * @param $contact
     * @return array
     * @throws Exception
     */
    public function setUserContact($name = '', $contact = '') {
        $user = Visitor::instance()->checkAuth();
        $userContact = [
            'name'    => $name,
            'contact' => $contact
        ];
        $user->contact = JsonUtils::json_stringify($userContact);
        $user->update();
        return $userContact;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getGuideInfo() {
        $user = Visitor::instance()->checkAuth();
        // 用户简介
        /** @var MUserInfo $info */
        $info = $user->info()->first();
        // 地址列表
        $addressList = UserRepository::getUserAddresses($user);
        // 联系方式
        $contactData = UserFormatter::contact($user);

        return [
            'info'     => $info ? $info->info : '',
            'address'  => $addressList,
            'contact'  => $contactData
        ];
    }

    /**
     * @param string $userId
     * @return array
     * @throws Exception
     */
    public function getHomepageData($userId = '') {
        $isFollowing = false;
        if (empty($userId)) {
            $user = Visitor::instance()->checkAuth();
            $userId = $user->id;
            $isMe = true;
        } else {
            $user = MUser::find($userId);
            if (!$user) {
                throw new Exception(Exception::RESOURCE_NOT_FOUND, '用户不存在');
            }
            $isMe = Visitor::instance()->isMe($userId);
            if (Visitor::instance()->getUser() !== null) {
                $isFollowing = UserRepository::isFollowing(
                    Visitor::instance()->getUserId(), $userId);
            }
        }
        // 用户简介
        /** @var MUserInfo $info */
        $info = $user->info()->first();
        // 地址列表
        $addressList = UserRepository::getUserAddresses($user);

        // 最新的三条读书卡片
        $cards = $user->cards()
            ->orderByDesc('create_time')
            ->take(3)->get();
        $formattedCards = UserFormatter::cardList($cards);
        // 读书卡片总数
        $cardCount = $user->cardCount();

        // 图书列表
        $bookCount = $user->bookCount();
        $borrowBooks = $user->borrowBooks()
            ->orderByDesc('id')
            ->take(5)
            ->get();
        $formattedBorrowBooks = [];
        /** @var MUserBook $userBook */
        foreach ($borrowBooks as $borrowBook) {
            $formattedBorrowBooks[] = [
                'isbn'      => $borrowBook->isbn,
                'title'     => $borrowBook->title,
                'author'    => json_decode($borrowBook->author),
                'cover'     => $borrowBook->cover,
                'publisher' => $borrowBook->publisher,
                'canBorrow' => !$isMe,
                'leftCount' => $borrowBook->left_count,
            ];
        }
        $borrowBookCount = $user->borrowBookCount();


        return [
            'userId'          => $userId,
            'info'            => $info ? $info->info : '',
            'nickname'        => $user->nickname,
            'avatar'          => $user->avatar,
            'address'         => $addressList,
            'cards'           => $formattedCards,
            'cardCount'       => $cardCount,
            'borrowBooks'     => $formattedBorrowBooks,
            'borrowBookCount' => $borrowBookCount,
            'books'           => [],
            'bookCount'       => $bookCount,
            'isMe'            => $isMe,
            'followed'        => $isFollowing,
            'followerCount'   => $user->followerCount(),
            'followingCount'  => $user->followingCount(),
        ];
    }

    /**
     * @param string $userId
     * @return array
     * @throws Exception
     */
    public function getUserInfo($userId = '') {
        $user = $this->getUser($userId);
        return [
            'nickname' => $user->nickname,
            'avatar'   => $user->avatar,
        ];
    }

    /**
     * @param string $userId
     * @return mixed|string
     * @throws Exception
     */
    public function info($userId = '') {
        $user = $this->getUser($userId);
        /** @var MUserInfo $info */
        $info = $user->info()->first();
        return $info ? $info->info : '';
    }

    /**
     * @param $info
     * @return mixed
     * @throws Exception
     */
    public function setInfo($info) {
        $user = Visitor::instance()->checkAuth();
        MUserInfo::updateOrInsert(['user_id' => $user->id],
            ['info' => $info]);
        return $info;
    }

    /**
     * @param $nickname
     * @param $intro
     * @param $avatar
     * @return string
     * @throws Exception
     */
    public function updateHomeData($nickname, $intro = '', $avatar = '') {
        $user = Visitor::instance()->checkAuth();

        // 鉴黄
        if (PulpManager::checkPulp($avatar, [
            'user_id' => $user->id,
            'title'   => '设置头像',
            'content' => '',
        ])) {
            throw new Exception(Exception::RESOURCE_IS_PULP, '你的图片不符合规范，不可以在有读书房使用');
        }

        // 更新个人简介
        $user->info()->update(['info' => $intro]);

        // 更新用户名 + 头像，头像后面拼的是七牛图片压缩的代码，
        // 存数据库应该直接存原图的，存压缩后的有点不合理，历史原因，暂时保留
        $user->nickname = $nickname;
        if (!empty($avatar)) {
            $user->avatar = $avatar . '?imageView2/1/w/640/h/640/format/jpg/q/75|imageslim';
        }
        $user->save();
        return 'ok';
    }

    /**
     * @param $userId
     * @param $all
     * @return array
     * @throws Exception
     */
    public function getUserBooks($userId, $all) {
        $user = MUser::find($userId);
        if (!$user) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND , '用户不存在~');
        }
        $isMe = Visitor::instance()->isMe($user->id);

        if (intval($all) === 0) {
            $books = $user->books()
                ->orderByDesc('id')
                ->get();
        } else {
            $books = $user->borrowBooks()
                ->orderByDesc('id')
                ->get();
        }

        $result = [];
        foreach ($books as $book) {
            $result[] = [
                'isbn'      => $book->isbn,
                'title'     => $book->title,
                'author'    => json_decode($book->author),
                'cover'     => $book->cover,
                'publisher' => $book->publisher,
                'canBorrow' => !$isMe && ($book->can_be_borrowed === MUserBook::BOOK_CAN_BE_BORROWED),
                'leftCount' => $book->left_count,
            ];
        }
        return $result;
    }

    /**
     * 这个接口只用于设置界面的我的书，可否借阅的字段仅用来标识闲置图书状态，不用于显示借阅按钮
     *
     * @param $all
     * @return array
     * @throws Exception
     */
    public function getMyBooks($all) {
        $user = Visitor::instance()->checkAuth();
        if (intval($all) === 0) {
            $books = $user->books()
                ->orderByDesc('id')
                ->get();
        } else {
            $books = $user->borrowBooks()
                ->orderByDesc('id')
                ->get();
        }
        $result = [];
        foreach ($books as $book) {
            $result[] = [
                'isbn'      => $book->isbn,
                'title'     => $book->title,
                'author'    => json_decode($book->author),
                'cover'     => $book->cover,
                'publisher' => $book->publisher,
                'canBorrow' => $book->can_be_borrowed === MUserBook::BOOK_CAN_BE_BORROWED,
                'leftCount' => $book->left_count,
            ];
        }
        return $result;
    }

    /**
     * 新的添加图书接口，为了减少服务器被豆瓣墙掉的概率，从豆瓣获取图书信息的逻辑放在客户端
     * （其实目前豆瓣获取图书的接口是从自己的服务器转发的，所以应该并不能减少服务器访问豆瓣的次数？）
     *
     * @param $book
     * @return string
     * @throws Exception
     */
    public function addNewBook($book) {
        Visitor::instance()->checkAuth();
        $doubanBook = json_decode($book);
        $this->addBookFromDoubanBook($doubanBook);
        return 'ok';
    }

    /**
     * 挪到客户端了,可以删了
     *
     * @param $isbn
     * @return mixed
     * @throws Exception
     */
    public function addBook($isbn) {
        Visitor::instance()->checkAuth();
        // check book in Douban
        $url = "https://api.douban.com/v2/book/{$isbn}";
        $response = file_get_contents($url);
        $doubanBook = json_decode($response);
        $this->addBookFromDoubanBook($doubanBook);
        return $isbn;
    }

    /**
     * @param $doubanBook
     * @throws Exception
     */
    private function addBookFromDoubanBook($doubanBook) {
        if ($doubanBook === null || empty($doubanBook->id)) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '无法获取图书信息');
        }

        // 备份豆瓣图书信息到有读
        $book = DoubanManager::copy($doubanBook);

        $user = Visitor::instance()->getUser();
        $hasBook = MUserBook::where(['user_id' => $user->id, 'isbn' => $doubanBook->id])
                ->count() > 0;
        if ($hasBook) {
            throw new Exception(Exception::RESOURCE_ALREADY_ADDED , '不可以添加重复的图书哦~');
        } else {
            $userBook = MUserBook::create([
                'user_id'         => $user->id,
                'isbn'            => $book->isbn,
                'create_time'     => time(),
                'can_be_borrowed' => MUserBook::BOOK_CAN_BE_BORROWED,
                'count'     => 1,
                'left_count'      => 1, // 暂时默认都是 1 本书
            ]);
            if ($userBook) {
                // 检查并添加新图书到发现流
                DiscoverManager::addNewBookToDiscoverFlow($book, $userBook);
            }
        }
    }

    /**
     * @param $isbn
     * @param $canBeBorrowed
     * @return string
     * @throws Exception
     */
    public function markBookAs($isbn, $canBeBorrowed) {
        $user = Visitor::instance()->checkAuth();
        $userBook = MUserBook::where([
            'user_id' => $user->id,
            'isbn'    => $isbn,
        ])->first();
        if (!$userBook) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND , '图书不存在');
        }

        $canBeBorrowedInt = intval($canBeBorrowed);
        if ($canBeBorrowedInt !== MUserBook::BOOK_CAN_BE_BORROWED
            && $canBeBorrowedInt !== MUserBook::BOOK_CANNOT_BE_BORROWED) {
            $canBeBorrowedInt = MUserBook::BOOK_CAN_BE_BORROWED;
        }
        $userBook->can_be_borrowed = $canBeBorrowedInt;
        $userBook->update();
        return 'ok';
    }

    /**
     * @param $isbn
     * @return mixed
     * @throws Exception
     */
    public function removeBook($isbn) {
        $user = Visitor::instance()->checkAuth();
        try {
            $result = MUserBook::where([
                'user_id' => $user->id,
                'isbn' => $isbn,
            ])->delete();
        } catch (\Exception $e) {
            $result = false;
        }
        if ($result) {
            // 图书从发现流中删除
            MDiscoverFlow::where([
                'user_id' => $user->id,
                'content_id' => $isbn,
            ])->update(['status' => MDiscoverFlow::DISCOVER_ITEM_USER_DELETED]);
            return $isbn;
        } else {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '无法删除未添加的图书~');
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getMyAddress() {
        $user = Visitor::instance()->checkAuth();
        return UserRepository::getUserAddresses($user);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getAddressCities() {
        $user = Visitor::instance()->checkAuth();
        return UserRepository::getUserAddresses($user);
    }

    /**
     * @param $id
     * @return mixed
     * @throws Exception
     */
    public function removeAddress($id) {
        $user = Visitor::instance()->checkAuth();
        try {
            MUserAddress::where([
                'id'      => $id,
                'user_id' => $user->id,
            ])->delete();
        } catch (\Exception $e) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '无法删除未添加的地址~');
        }

        return $id;
    }

    /**
     * @param $name
     * @param $detail
     * @param $latitude
     * @param $longitude
     * @return mixed
     * @throws Exception
     */
    public function addAddress($name, $detail, $latitude, $longitude) {
        $user = Visitor::instance()->checkAuth();
        $addressList = $user->addresses()->get();
        if ($addressList && $addressList->count() >= 3) {
            throw new Exception(Exception::BAD_REQUEST , '最多添加三个地址~');
        }

        // 判断一下 2 公里内不能添加多个地址
        foreach ($addressList as $addressItem) {
            /** @var MUserAddress $addressItem */
            if (abs($latitude - $addressItem->latitude) <= 0.0038
                && abs($longitude - $addressItem->longitude) <= 0.0034) {
                throw new Exception(Exception::BAD_REQUEST , '你已经在附近添加过一个地址了');
            }
        }

        MUserAddress::create([
            'user_id'   => $user->id,
            'address'   => $name,
            'detail'    => $detail,
            'latitude'  => $latitude,
            'longitude' => $longitude,
            'city'      => BaiduPoiManager::reversePoi($latitude, $longitude),
        ]);
        return $name;
    }

    /**
     * 0 - 未处理
     * 1 - 已同意
     * 2 - 已拒绝
     * 3 - 已忽略
     * @throws Exception
     */
    public function getMyApprovedRequest() {
        $user = Visitor::instance()->checkAuth();
        $approvedList = $user->approvedList()->get();
        $result = [];
        foreach ($approvedList as $approved) {
            $result[] = [
                'requestId' => $approved->history_id,
                'userId'    => $approved->to_user,
                'user'      => $approved->nickname,
                'bookTitle' => $approved->book_title,
                'bookCover' => $approved->book_cover,
                'date'      => $approved->date,
                'status'    => $approved->status,
            ];
        }
        return $result;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getBorrowHistory() {
        $user = Visitor::instance()->checkAuth();
        $borrowHistoryList = $user->borrowHistory()->get();
        $result = [];
        foreach ($borrowHistoryList as $borrowHistory) {
            $result[] = [
                'requestId' => $borrowHistory->history_id,
                'userId'    => $borrowHistory->to_user,
                'user'      => $borrowHistory->nickname,
                'bookTitle' => $borrowHistory->book_title,
                'bookCover' => $borrowHistory->book_cover,
                'date'      => $borrowHistory->date,
                'status'    => $borrowHistory->status,
            ];
        }
        return $result;
    }

    /**
     * @param $out
     * @return array
     * @throws Exception
     */
    public function getBorrowOrders($out) {
        $user = Visitor::instance()->checkAuth();
        $orderList = $user->borrowOrders($out)->get();
        $result = [];
        foreach ($orderList as $order) {
            $result[] = [
                'id'   => $order->history_id,
                'user' => [
                    'id'       => $order->user_id,
                    'nickname' => $order->nickname,
                    'avatar'   => $order->avatar
                ],
                'book' => [
                    'isbn'      => $order->isbn,
                    'title'     => $order->title,
                    'author'    => BookFormatter::parseAuthor($order->author),
                    'cover'     => $order->cover,
                    'publisher' => $order->publisher,
                ],
                'date' => $order->date,
            ];
        }
        return $result;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getBorrowRequestCount() {
        $user = Visitor::instance()->checkAuth();
        $count = MBorrowHistory::where([
            'to_user' => $user->id,
            'status'  => MBorrowHistory::BORROW_STATUS_INIT,
        ])->count();
        return $count;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getBorrowRequest() {
        $user = Visitor::instance()->checkAuth();
        $borrowRequests = $user->borrowRequests()->get();
        $result = [];
        foreach ($borrowRequests as $borrowRequest) {
            $result[] = [
                'requestId'  => $borrowRequest->history_id,
                'fromUser'   => $borrowRequest->nickname,
                'fromUserId' => $borrowRequest->from_user,
                'bookTitle'  => $borrowRequest->book_title,
                'bookCover'  => $borrowRequest->book_cover,
                'date'       => $borrowRequest->date,
                'status'     => $borrowRequest->status,
            ];
        }
        return $result;
    }

    // 预约

    /**
     * @param $toUser
     * @param $isbn
     * @param $formId
     * @return string
     * @throws Exception
     */
    public function borrowBook($toUser, $isbn, $formId) {
        $self = Visitor::instance()->checkAuth();
        if (intval($toUser) === $self->id) {
            throw new Exception(Exception::BAD_REQUEST , '不可以借自己的书哦~');
        }

        // check user exist
        $user = MUser::find($toUser);
        if (!$user) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND , '用户不存在~');
        }

        // check book exist
        $book = MBook::find($isbn);
        if (!$book) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '无法获取图书信息');
        }
        $userBook = MUserBook::where([
            'user_id' => $user->id,
            'isbn'    => $isbn,
        ])->first();
        if (!$userBook) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '书似乎已经被书房主人移除了~');
        }
        // 客户端界面上会防的，这里也防一下
        if ($userBook->can_be_borrowed !== MUserBook::BOOK_CAN_BE_BORROWED) {
            throw new Exception(Exception::BAD_REQUEST, '这本书是非闲置图书~');
        }
        if ($userBook->left_count <= 0) {
            throw new Exception(Exception::BAD_REQUEST, '书似乎已经被书房主人借出去了~');
        }

        $date = date('Y-m-d'); // 这个当时为什么只存了个日期字符串，算了将错就错吧
        MBorrowHistory::create([
            'from_user'  => $self->id,
            'to_user'    => $toUser,
            'book_isbn'  => $book->isbn,
            'book_title' => $book->title,
            'book_cover' => $book->cover,
            'date'       => $date,
            'status'     => MBorrowHistory::BORROW_STATUS_INIT,
            'form_id'    => $formId,
        ]);

        // 插一条消息到聊天记录
        ChatRepository::sendRequest($self->id, $toUser,
            JsonUtils::json_stringify([
                'isbn'  => $book->isbn,
                'title' => $book->title,
                'cover' => $book->cover,
                'date'  => $date,
            ]));

        // 发通知短信
        if (!empty($user->mobile)) {
            AliSmsSender::sendBorrowBookSms(
                $user->mobile, $self->nickname, $book->title);
        }

        return 'ok';
    }

    /**
     * @param $requestId
     * @param $status
     * @return string
     * @throws Exception
     */
    public function updateBorrowRequest($requestId, $status) {
        $user = Visitor::instance()->checkAuth();

        $history = MBorrowHistory::find($requestId);
        if (!$history) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND , '借书请求不存在~');
        }

        if ($history->to_user !== $user->id) {
            throw new Exception(Exception::BAD_REQUEST , '不属于你的请求不能处理~');
        }

        if ($history->status !== MBorrowHistory::BORROW_STATUS_INIT) {
            throw new Exception(Exception::BAD_REQUEST , '重复请求~');
        }

        // 检查联系方式是不是设置了
        if ($status === MBorrowHistory::BORROW_STATUS_AGREED) {
            $userContact = UserFormatter::contact($user);
            if (empty($userContact)) {
                return 'no_contact';
            }
        }

        $history->status = $status;
        $history->update();

        // 发模板消息
        if ($status === MBorrowHistory::BORROW_STATUS_AGREED
            || $status === MBorrowHistory::BORROW_STATUS_DECLINED) {
            $fromUser = MUser::find($history->from_user);
            if ($fromUser) {
                if ($status === MBorrowHistory::BORROW_STATUS_AGREED) {
                    WxTemplateMessageManager::sendAgreeBorrowBookMessage(
                        $fromUser->wechat_open_id, $history->form_id, $history->book_title,
                        $user->nickname, $history->date
                    );
                } else if ($status === MBorrowHistory::BORROW_STATUS_DECLINED) {
                    WxTemplateMessageManager::sendDeclineBorrowBookMessage(
                        $fromUser->wechat_open_id, $history->form_id, $history->book_title,
                        $user->nickname, $history->date
                    );
                }
            }
        }

        return 'success';
    }

    /**
     * @param $toUser
     * @return string
     * @throws Exception
     */
    public function follow($toUser) {
        $self = Visitor::instance()->checkAuth();

        $toUser = intval($toUser);
        // check user exist
        $user = MUser::find($toUser);
        if (!$user) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND , '用户不存在~');
        }

        if ($toUser === $self->id) {
            throw new Exception(Exception::BAD_REQUEST , '不可以关注自己哦~');
        }

        MFollow::updateOrCreate([
            'from_id' => $self->id,
            'to_id'   => $toUser,
        ], [
            'create_time' => time(),
        ]);

        $extra = [
            'router' => 'follower',
        ];
        // 给被关注的同志发一条系统消息
        ChatRepository::sendSystemMessage(ChatRepository::BOCHA_SYSTEM_USER_ID,
            $toUser, "书友 {$self->nickname} 关注了你~",
            JsonUtils::json_stringify($extra));

        return 'ok';
    }

    /**
     * @param $toUser
     * @return string
     * @throws Exception
     */
    public function unfollow($toUser) {
        $user = Visitor::instance()->checkAuth();
        try {
            MFollow::where([
                'from_id' => $user->id,
                'to_id' => $toUser,
            ])->delete();
        } catch (\Exception $e) {
            throw new Exception(Exception::INTERNAL_ERROR , '操作失败请稍后重试~');
        }

        return 'ok';
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getMyFollowings() {
        $user = Visitor::instance()->checkAuth();
        return $this->getFollowingsByUser($user);
    }

    /**
     * @param $userId
     * @return array
     * @throws Exception
     */
    public function getUserFollowings($userId) {
        $user = MUser::find($userId);
        if (!$user) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '用户不存在');
        }
        return $this->getFollowingsByUser($user);
    }

    private function getFollowingsByUser(MUser $user) {
        $followings = $user->followings()->get();
        $result = [];
        /** @var MUser $following */
        foreach ($followings as $following) {
            $addresses = UserRepository::getUserAddresses($following);
            $result[] = [
                'id'        => $following->id,
                'nickname'  => $following->nickname,
                'avatar'    => $following->avatar,
                'address'   => $addresses,
                'bookCount' => $following->bookCount(),
            ];
        }

        return $result;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getMyFollowers() {
        $user = Visitor::instance()->checkAuth();
        return $this->getFollowersByUser($user);
    }

    /**
     * @param $userId
     * @return array
     * @throws Exception
     */
    public function getUserFollowers($userId) {
        $user = MUser::find($userId);
        if (!$user) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '用户不存在');
        }
        return $this->getFollowersByUser($user);
    }

    private function getFollowersByUser(MUser $user) {
        $followings = $user->followers()->get();
        $result = [];
        /** @var MUser $following */
        foreach ($followings as $following) {
            $addresses = UserRepository::getUserAddresses($following);
            $result[] = [
                'id'        => $following->id,
                'nickname'  => $following->nickname,
                'avatar'    => $following->avatar,
                'address'   => $addresses,
                'bookCount' => $following->bookCount(),
            ];
        }

        return $result;
    }


    /**
     * @param $mobile
     * @return string
     * @throws Exception
     */
    public function requestVerifyCode($mobile) {
        $user = Visitor::instance()->checkAuth(true);

        if (!empty($user->mobile) && $user->mobile === $mobile) {
            throw new Exception(Exception::RESOURCE_ALREADY_ADDED, '你已经绑定过这个手机号了~');
        }

        $verifyCode = MSmsCode::where([
            'user_id' => $user->id,
            'mobile'  => $mobile,
        ])->orderByDesc('create_time')->first();
        if ($verifyCode && (time() - $verifyCode->create_time) < 60) {
            throw new Exception(Exception::REQUEST_TOO_MUCH, '请求过于频繁,请稍后再试~');
        }

        $codeNum = CommonUtils::randCode(6, 1); // 6位数字
        if (AliSmsSender::sendVeriCodeSms($mobile, $codeNum)) {
            MSmsCode::updateOrInsert([
                'user_id' => $user->id,
                'mobile'  => $mobile,
            ],[
                'code'        => $codeNum,
                'create_time' => time(),
            ]);
            return 'ok';
        }

        throw new Exception(Exception::INTERNAL_ERROR, '验证码发送失败,请稍后再试~');
    }

    /**
     * @param $mobile
     * @param $code
     * @return string
     * @throws Exception
     */
    public function verifyCode($mobile, $code) {
        $user = Visitor::instance()->checkAuth(true);
        $verifyCode = MSmsCode::where([
            'user_id' => $user->id,
            'mobile'  => $mobile,
            'code'    => $code,
        ])->orderByDesc('create_time')->first();

        if (!$verifyCode) {
            throw new Exception(Exception::VERIFY_CODE_EXPIRED, '验证码填写错误，请检查后重试~');
        }

        if ($verifyCode->create_time + 300 <= time()) {
            throw new Exception(Exception::VERIFY_CODE_EXPIRED, '验证码已过期，请重新发送验证码~');
        }

        $user->mobile = $mobile;
        $user->update();
        return 'ok';
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getMyQRCode() {
        $user = Visitor::instance()->checkAuth();
        return [
            'avatar'   => $user->avatar,
            'nickname' => $user->nickname,
            'qrToken'  => "bocha://youdushufang/u/{$user->id}",
        ];
    }

    /**
     * @param mixed $userId
     * @return MUser
     * @throws Exception
     */
    private function getUser($userId) {
        if (empty($userId)) {
            $user = Visitor::instance()->checkAuth();
        } else {
            $user = MUser::find($userId);
            if (!$user) {
                throw new Exception(Exception::RESOURCE_NOT_FOUND, '用户不存在');
            }
        }
        return $user;
    }
}