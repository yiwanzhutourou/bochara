<?php

namespace App\Lib\Weixin;

use App\Http\Controllers\Api\Exceptions\ApiException;
use App\Lib\HttpClient\HttpClient;
use App\Lib\SentryHelper;

class WxTemplateMessageManager {

    public static function sendDeclineBorrowBookMessage(
        $toUserOpenId, $formId, $bookTitle, $bookOwner, $date) {
        return self::sendWxTemplateMessage(
            $toUserOpenId,
            'Sp_-WuvoHxYBzxAJMXtH2gop8AwDHuwnzREOr-QkTr4',
            'pages/user/history',
            $formId,
            [
                'keyword1' => ['value' => $bookTitle],
                'keyword2' => ['value' => $date],
                'keyword3' => ['value' => "书友 {$bookOwner} 拒绝了你借阅《{$bookTitle}》的请求,点击查看详情"],
            ],
            'keyword1.DATA'
        );
    }

    public static function sendAgreeBorrowBookMessage(
        $toUserOpenId, $formId, $bookTitle, $bookOwner, $date) {
        return self::sendWxTemplateMessage(
            $toUserOpenId,
            'Sp_-WuvoHxYBzxAJMXtH2gop8AwDHuwnzREOr-QkTr4',
            'pages/message/approved',
            $formId,
            [
                'keyword1' => ['value' => $bookTitle],
                'keyword2' => ['value' => $date],
                'keyword3' => ['value' => "书友 {$bookOwner} 同意了你借阅《{$bookTitle}》的请求,点击查看详情"]
            ],
            'keyword1.DATA'
        );
    }

    public static function sendBorrowBookMessage(
        $toUserOpenId, $formId, $bookTitle, $fromUserNick) {
        return self::sendWxTemplateMessage(
            $toUserOpenId,
            'Sp_-WuvoHxYBzxAJMXtH2uPu7Iw-AtY2fS-zWRuroU4',
            'pages/user/request',
            $formId,
            [
                'keyword1' => ['value' => $bookTitle],
                'keyword2' => ['value' => "书友 {$fromUserNick} 想借阅你书房里的《{$bookTitle}》,点击查看详情"],
                'keyword3' => ['value' => $fromUserNick],
                'keyword4' => ['value' => date('Y-m-d H:m')]
            ],
            'keyword1.DATA'
        );
    }

    /**
     * @param $toUserOpenId
     * @param $templateId
     * @param $page
     * @param $formId
     * @param array $data
     * @param $keyword
     * @return bool
     */
    private static function sendWxTemplateMessage(
        $toUserOpenId, $templateId,
        $page, $formId, $data = [], $keyword) {
        $access_token = WxAccessTokenManager::instance()->getAccessToken();
        if ($access_token === false) {
            return false;
        }

        $result = self::sendToWeixin($access_token, $toUserOpenId, $templateId,
            $page, $formId, $data, $keyword);
        if (!$result) {
            return false;
        }

        $json = json_decode($result);
        if ($json && (!empty($json->errcode) || $json->errcode > 0)) {
            // 线上环境清空token重试一次
            if (env('APP_ENV') === 'production') {
                $access_token = WxAccessTokenManager::instance()->getAccessToken(true);
                $result = self::sendToWeixin($access_token, $toUserOpenId, $templateId,
                    $page, $formId, $data, $keyword);
                if (!$result) {
                    return false;
                }
                $json = json_decode($result);
                if (!empty($json->errcode) || $json->errcode > 0) {
                    SentryHelper::report(new ApiException($json->errcode, $result));
                    return false;
                }
            }
            return false;
        }

        return true;
    }

    private static function sendToWeixin($access_token, $toUserOpenId, $templateId,
                                         $page, $formId, $data, $keyword) {
        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send";
        return HttpClient::post($url, [
            'query'   => ['access_token' => $access_token],
            'json'    => [
                'touser'           => $toUserOpenId,
                'template_id'      => $templateId,
                'page'             => $page,
                'form_id'          => $formId,
                'data'             => $data,
                'emphasis_keyword' => $keyword,
            ],
            'timeout' => 60,
        ]);
    }
}