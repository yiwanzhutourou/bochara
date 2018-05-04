<?php

namespace App\Repositories;

use App\Models\MChat;
use App\Models\MChatMessage;
use App\Models\MUser;
use App\Utils\JsonUtils;

class ChatRepository {

    const BOCHA_SYSTEM_USER_ID = 0;

    public static function sendRequest($from, $to, $request) {
        // update both user in chat table
        self::updateChat(
            $from, $to, $from, '', MChatMessage::MSG_TYPE_BORROW, $request);
        self::updateChat(
            $to, $from, $from, '', MChatMessage::MSG_TYPE_BORROW, $request);

        // insert a new message
        self::insertNewMessage(
            $from, $to, '', MChatMessage::MSG_TYPE_BORROW, $request);
    }

    public static function sendContact($from, $to, $contact) {
        // update both user in chat table
        self::updateChat(
            $from, $to, $from, '', MChatMessage::MSG_TYPE_CONTACT, $contact);
        self::updateChat(
            $to, $from, $from, '', MChatMessage::MSG_TYPE_CONTACT, $contact);

        // insert a new message
        self::insertNewMessage(
            $from, $to, '', MChatMessage::MSG_TYPE_CONTACT, $contact);
    }

    public static function sendMessage($from, $to, $message) {
        // update both user in chat table
        self::updateChat(
            $from, $to, $from, $message, MChatMessage::MSG_TYPE_TEXT, '');
        self::updateChat(
            $to, $from, $from, $message, MChatMessage::MSG_TYPE_TEXT, '');

        // insert a new message
        self::insertNewMessage(
            $from, $to, $message, MChatMessage::MSG_TYPE_TEXT, '');
    }

    public static function sendSystemMessage($from, $to, $message, $extra) {
        self::updateChat(
            $to, $from, $from, $message, MChatMessage::MSG_TYPE_SYSTEM, $extra);

        // insert a new message
        self::insertNewMessage(
            $from, $to, $message, MChatMessage::MSG_TYPE_SYSTEM, $extra);
    }

    public static function sendNewPostMessage($card, $nickname) {
        $extra = [
            'router' => 'newcard',
            'extra'  => $card->id,
        ];

        $adminIds = ['34', '35'];
        foreach ($adminIds as $adminId) {
            self::sendSystemMessage(self::BOCHA_SYSTEM_USER_ID, $adminId,
                "[系统消息] 用户 {$nickname} 发布了一个新的读书卡片: {$card->title}。",
                JsonUtils::json_stringify($extra));
        }
    }

    public static function sendRepostMessage($card, $nickname) {
        $extra = [
            'router' => 'newcard',
            'extra'  => $card->id,
        ];

        $adminIds = ['34', '35'];
        foreach ($adminIds as $adminId) {
            self::sendSystemMessage(self::BOCHA_SYSTEM_USER_ID, $adminId,
                "[系统消息] 用户 {$nickname} 修改了一个读书卡片: {$card->title}。",
                JsonUtils::json_stringify($extra));
        }
    }

    public static function insertNewMessage($from, $to, $message, $msgType, $extra) {
        MChatMessage::create([
            'user_1'      => $from,
            'user_2'      => $to,
            'msg_content' => $message,
            'msg_type'    => $msgType,
            'status_1'    => MChatMessage::MSG_STATUS_NORMAL,
            'status_2'    => MChatMessage::MSG_STATUS_NORMAL,
            'timestamp'   => time(),
            'extra'       => $extra,
        ]);
    }

    public static function updateChat(
        $user1, $user2, $sender, $message, $msgType, $extra) {

        $isReceiver = $user1 !== $sender;
        $chat = MChat::updateOrCreate([
            'user_1' => $user1,
            'user_2' => $user2,
        ], [
            'msg_content' => $message,
            'msg_sender'  => $sender,
            'msg_type'    => $msgType,
            'status'      => MChatMessage::MSG_STATUS_NORMAL,
            'timestamp'   => time(),
            'extra'       => $extra,
        ]);
        if ($chat && $isReceiver) {
            $chat->increment('unread_count');
        }
    }

    public static function clearUnread($user1, $user2) {
        MChat::where([
            'user_1' => $user1,
            'user_2' => $user2,
        ])->update(['unread_count' => 0]);
    }

    public static function deleteChatMessage($user1, $user2, $timestamp) {
        // user1 要删除和 user2 的之间的消息

        // user1 发送给 user2 的消息，status1 字段标记为删除（发送方）
        // 这里不用判断时间戳直接都删除，因为不存在误删未读消息的情况
        MChatMessage::where([
            'user_1' => $user1,
            'user_2' => $user2,
        ])->update(['status_1' => MChatMessage::MSG_STATUS_DELETED]);

        // user2 发送给 user1 的消息，status2 字段标记为删除（接收方）
        MChatMessage::where(['user_1' => $user2, 'user_2' => $user1])
            ->where('timestamp', '<', intval($timestamp))
            ->update(['status_2' => MChatMessage::MSG_STATUS_DELETED]);

        // 同时更新chat表
        // （不知道是不是想复杂了）因为在删除的时间点之后 user2 可能又发了消息过来,
        // 所以在更新完 message 表之后还应该去查询一下是否有 user2 发过来的没被删除的新消息
        $newMessageCount = MChatMessage::where([
            'user_1'   => $user2,
            'user_2'   => $user1,
            'status_2' => MChatMessage::MSG_STATUS_NORMAL,

        ])->count();
        if ($newMessageCount <= 0) {
            // 没有新消息，直接把这条 chat 的状态标记为删除
            MChat::where([
                'user_1' => $user1,
                'user_2' => $user2,
            ])->update([
                'status'       => MChatMessage::MSG_STATUS_DELETED,
                'unread_count' => 0,
            ]);
        } else {
            $newMessage = MChatMessage::where([
                'user_1'   => $user2,
                'user_2'   => $user1,
                'status_2' => MChatMessage::MSG_STATUS_NORMAL,

            ])->first();
            MChat::where([
                'user_1' => $user1,
                'user_2' => $user2,
            ])->update([
                'msg_content'  => $newMessage->msg_content,
                'msg_sender'   => $newMessage->user_1,
                'msg_type'     => $newMessage->msg_type,
                'status'       => MChatMessage::MSG_STATUS_NORMAL,
                'timestamp'    => $newMessage->timestamp,
                'unread_count' => $newMessageCount,
                'extra'        => $newMessage->extra,
            ]);
        }
    }

    public static function createSystemUser() {
        $systemUser = new MUser();
        $systemUser->id = self::BOCHA_SYSTEM_USER_ID;
        $systemUser->nickname = '有读书房';
        $systemUser->avatar = 'http://othb16dht.bkt.clouddn.com/Fm3qYpsmNFGRDbWeTOQDRDfiJz9l?imageView2/1/w/640/h/640/format/jpg/q/75|imageslim';
        return $systemUser;
    }

    public static function createMessage($message) {
        if (!$message) {
            return [];
        }
        /** @var MChatMessage $message */
        switch ($message->msg_type) {
            case MChatMessage::MSG_TYPE_TEXT:
                return [
                    'type'      => 'message',
                    'from'      => $message->user_1,
                    'to'        => $message->user_2,
                    'content'   => $message->msg_content,
                    'timeStamp' => $message->timestamp,
                ];
            case MChatMessage::MSG_TYPE_BORROW:
                $extra = json_decode($message->extra);
                return [
                    'type'      => 'request',
                    'from'      => $message->user_1,
                    'to'        => $message->user_2,
                    'content'   => $message->msg_content,
                    'timeStamp' => $message->timestamp,
                    'extra'     => $extra,
                ];
            case MChatMessage::MSG_TYPE_CONTACT:
                $extra = json_decode($message->extra);
                return [
                    'type'      => 'contact',
                    'from'      => $message->user_1,
                    'to'        => $message->user_2,
                    'content'   => $message->msg_content,
                    'timeStamp' => $message->timestamp,
                    'extra'     => $extra,
                ];
            case MChatMessage::MSG_TYPE_SYSTEM:
                $extra = json_decode($message->extra);
                return [
                    'type'      => 'system',
                    'from'      => $message->user_1,
                    'to'        => $message->user_2,
                    'content'   => $message->msg_content,
                    'timeStamp' => $message->timestamp,
                    'extra'     => $extra,
                ];
            default:
                return [
                    'type' => 'unknown',
                    'from' => $message->user_1,
                    'to'   => $message->user_2,
                ];
        }
    }

    public static function createFakeMessage() {
        return [
            'type'      => 'fake_hint',
            'from'      => '',
            'to'        => '',
            'content'   => '提示：有读书房的留言并不是及时聊天，你可以尝试点击刷新来获取更新的消息',
            'timeStamp' => '',
        ];
    }
}