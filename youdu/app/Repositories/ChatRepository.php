<?php

namespace App\Repositories;

use App\Models\MChat;
use App\Models\MChatMessage;
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
}