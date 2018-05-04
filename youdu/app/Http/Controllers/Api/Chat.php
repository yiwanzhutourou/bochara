<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Exceptions\Exception;
use App\Http\Controllers\Api\Lib\Visitor;
use App\Lib\SMS\AliSmsSender;
use App\Models\MBook;
use App\Models\MBorrowHistory;
use App\Models\MChat;
use App\Models\MChatMessage;
use App\Models\MUser;
use App\Models\MUserBook;
use App\Repositories\ChatRepository;
use App\Utils\JsonUtils;

class Chat extends ApiBase {

    /**
     * 删除与另一个用户之间的所有消息，目前暂不支持某一条单独删除
     * start() 和 getChatList() 两个接口会下发获取到数据的时间戳，
     * 删除时把这个时间戳作为参数传到服务端，服务端会删除这个时间戳之前的消息，
     * 这样能保证删除的都是用户已经拉取过的数据，而不会误删新发的而用户还没有看到的消息
     *
     * @param $otherId
     * @param $timestamp
     * @return string
     * @throws Exception
     */
    public function delete($otherId, $timestamp) {
        $user = Visitor::instance()->checkAuth();

        if (intval($otherId) !== ChatRepository::BOCHA_SYSTEM_USER_ID) {
            // check user exist
            $otherUser = MUser::find($otherId);
            if (!$otherUser) {
                throw new Exception(Exception::RESOURCE_NOT_FOUND , '用户不存在~');
            }
        }

        ChatRepository::deleteChatMessage($user->id, $otherId, $timestamp);
        return 'ok';
    }

    /**
     * @param $toUser
     * @param $isbn
     * @param $message
     * @return string
     * @throws Exception
     */
    public function borrowBook($toUser, $isbn, $message = '') {
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
            throw new Exception(Exception::WEIXIN_AUTH_FAILED, '无法获取图书信息');
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
            'form_id'    => '',
        ]);

        // 插一条消息到聊天记录
        $requestExtra = [
            'isbn'  => $book->isbn,
            'title' => $book->title,
            'cover' => $book->cover,
            'date'  => $date,
        ];
        ChatRepository::sendRequest(
            $self->id, $toUser, JsonUtils::json_stringify($requestExtra));

        // 最新的接口借书会带一条消息,直接当做发送了一条普通消息
        if (!empty($message)) {
            ChatRepository::sendMessage($self->id, $toUser, $message);
        }

        // 发通知短信
        if (!empty($user->mobile)) {
            AliSmsSender::sendBorrowBookSms(
                $user->mobile, $self->nickname, $book->title);
        }

        return 'ok';
    }

    /**
     * @param $otherId
     * @param $message
     * @return string
     * @throws Exception
     */
    public function sendMessage($otherId, $message) {
        $self = Visitor::instance()->checkAuth();

        // check user exist
        $otherUser = MUser::find($otherId);
        if (!$otherUser) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND , '用户不存在~');
        }

        ChatRepository::sendMessage($self->id, $otherId, $message);
        return 'ok';
    }

    /**
     * @param $otherId
     * @return mixed|string
     * @throws Exception
     */
    public function sendContact($otherId) {
        $self = Visitor::instance()->checkAuth();

        // check user exist
        $otherUser = MUser::find($otherId);
        if (!$otherUser) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND , '用户不存在~');
        }

        $hasContact = false;
        $contactJson = $self->contact;
        $contact = json_decode($contactJson);
        if (isset($contact->name) && isset($contact->contact)) {
            if (in_array($contact->name, ['微信', 'QQ', '邮箱'])
                && !empty($contact->contact)) {
                $hasContact = true;
            }
        }

        if (!$hasContact) {
            return 'no';
        }

        ChatRepository::sendContact($self->id, $otherId, $contactJson);
        return $contactJson;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getChatList() {
        $self = Visitor::instance()->checkAuth();
        $chats = $self->chats()->get();
        $result = [];
        foreach ($chats as $chat) {
            /** @var MChat $chat */
            $selfId = $chat->user_1;
            $otherId = $chat->user_2;
            $isSend = $selfId === $chat->msg_sender;
            /** @var MUser $otherUser */
            if ($otherId === ChatRepository::BOCHA_SYSTEM_USER_ID) {
                $otherUser = ChatRepository::createSystemUser();
            } else {
                $otherUser = MUser::find($otherId);
            }
            if (!$otherUser) {
                continue;
            }

            switch ($chat->msg_type) {
                case MChatMessage::MSG_TYPE_TEXT:
                case MChatMessage::MSG_TYPE_SYSTEM:
                    $message = $chat->msg_content;
                    break;
                case MChatMessage::MSG_TYPE_BORROW:
                    $extra = json_decode($chat->extra);
                    if ($isSend) {
                        $message = "你想要借阅{$otherUser->nickname}的《{$extra->title}》";
                    } else {
                        $message = "{$otherUser->nickname}想要借阅你的《{$extra->title}》";
                    }
                    break;
                case MChatMessage::MSG_TYPE_CONTACT:
                    $extra = json_decode($chat->extra);
                    if ($isSend) {
                        $message = "你向{$otherUser->nickname}发送了{$extra->name}";
                    } else {
                        $message = "{$otherUser->nickname}向你发送了{$extra->name}";
                    }
                    break;
                default:
                    $message = '';
            }
            $result[] = [
                'user'        => [
                    'id'       => $otherUser->id,
                    'nickname' => $otherUser->nickname,
                    'avatar'   => $otherUser->avatar,
                ],
                'message'     => $message,
                'timeStamp'   => $chat->timestamp,
                'unreadCount' => $chat->unread_count,
            ];
        }

        // 系统消息置顶
        for ($i = count($result) - 1; $i >= 0; $i--) {
            $userId = array_values($result)[$i]['user']['id'];
            if (intval($userId)
                === ChatRepository::BOCHA_SYSTEM_USER_ID) {
                $tmp = $result[$i];
                unset($result[$i]);
                array_unshift($result, $tmp);
                break;
            }
        }

        return [
            'messages'  => $result,
            'timestamp' => time(),
        ];
    }

    /**
     * @param $otherId
     * @param $timestamp
     * @return array
     * @throws Exception
     */
    public function getNew($otherId, $timestamp) {
        $self = Visitor::instance()->checkAuth();

        if (intval($otherId) !== ChatRepository::BOCHA_SYSTEM_USER_ID) {
            // check user exist
            $otherUser = MUser::find($otherId);
            if (!$otherUser) {
                throw new Exception(Exception::RESOURCE_NOT_FOUND , '用户不存在~');
            }
        }
        $selfId = $self->id;

        // 有没有其他写法？
        $queryString = "(((user_1 = {$selfId} and user_2 = {$otherId} and status_1 = 0)"
            . " or (user_1 = {$otherId} and user_2 = {$selfId} and status_2 = 0))"
            . " and (timestamp > {$timestamp}))";
        $messages = MChatMessage::whereRaw($queryString)
            ->orderByDesc('timestamp')
            ->get();

        $formattedMessages = [];
        foreach ($messages as $message) {
            $formattedMessages[] = ChatRepository::createMessage($message);
        }

        return array_reverse($formattedMessages);
    }

    /**
     * @param $otherId
     * @param int $count
     * @param int $page
     * @return array
     * @throws Exception
     */
    public function start($otherId, $count = 15, $page = 0) {
        $self = Visitor::instance()->checkAuth();

        // check user exist
        /** @var MUser $otherUser */
        if (intval($otherId) === ChatRepository::BOCHA_SYSTEM_USER_ID) {
            $otherUser = ChatRepository::createSystemUser();
        } else {
            $otherUser = MUser::find($otherId);
        }
        if (!$otherUser) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND , '用户不存在~');
        }
        $selfId = $self->id;

        $offset = $page * $count;
        $queryString = "((user_1 = {$selfId} and user_2 = {$otherId} and status_1 = 0)"
            . " or (user_1 = {$otherId} and user_2 = {$selfId} and status_2 = 0))";
        $messages = MChatMessage::whereRaw($queryString)
            ->orderByDesc('timestamp')
            ->skip($offset)
            ->take($count)
            ->get();

        $formattedMessages = [];
        foreach ($messages as $message) {
            $formattedMessages[] = ChatRepository::createMessage($message);
        }
        $formattedMessages = array_reverse($formattedMessages);

        if (intval($page) === 0 && intval($otherId) !== ChatRepository::BOCHA_SYSTEM_USER_ID) {
            // 所有聊天开始默认推一条 hint
            $formattedMessages[] = ChatRepository::createFakeMessage();
        }

        // clear unread count
        ChatRepository::clearUnread($selfId, $otherId);

        return [
            'self'  => [
                'id'       => $self->id,
                'nickname' => $self->nickname,
                'avatar'   => $self->avatar,
            ],
            'other' => [
                'id'       => $otherUser->id,
                'nickname' => $otherUser->nickname,
                'avatar'   => $otherUser->avatar,
            ],
            'messages'  => $formattedMessages,
            'timestamp' => time(),
        ];
    }
}