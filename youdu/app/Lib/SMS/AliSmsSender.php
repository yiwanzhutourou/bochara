<?php

namespace App\Lib\SMS;

use Mrgoon\AliSms\AliSms;

class AliSmsSender {
    public static function sendBorrowBookSms($phoneNumber, $fromUserName, $bookName) {
        $params = [
            'from_user_name'   => $fromUserName,
            'borrow_book_name' => $bookName
        ];
        $sms = new AliSms();
        $response = $sms->sendSms(
            $phoneNumber, 'SMS_76385138', $params);
        if ($response && $response->Code === 'OK') {
            return true;
        }
        // TODO 通知一下，防止线上大量短信发送失败
        return false;
    }

    public static function sendVeriCodeSms($phoneNumber, $code) {
        $params = [
            'verify_code'   => $code
        ];
        $sms = new AliSms();
        $response = $sms->sendSms(
            $phoneNumber, 'SMS_76440167', $params);
        if ($response && $response->Code === 'OK') {
            return true;
        }
        return false;
    }

    public static function sendNewFeatureSms($phoneNumber, $newFeature) {
        $params = [
            'new_feature' => $newFeature,
        ];
        $sms = new AliSms();
        $response = $sms->sendSms(
            $phoneNumber, 'SMS_89945049', $params);
        if ($response && $response->Code === 'OK') {
            return true;
        }
        return false;
    }

}