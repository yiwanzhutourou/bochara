<?php

namespace App\Lib\Weixin;

use App\Http\Controllers\Api\Exceptions\Exception;
use App\Models\MXu;

class WxAccessTokenManager {

    private static $instance = null;

    const TOKEN_KEY = 'wx_access_token';

    private $access_token = '';
    private $expire_time = -1;

    private function __construct() {}

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new WxAccessTokenManager();
        }
        return self::$instance;
    }

    /**
     * @return string|bool
     */
    public function getAccessToken() {
        if (!empty($this->access_token) && $this->tokenValid()) {
            return $this->access_token;
        }

        // TODO 改用 Redis
        $xu = MXu::where(['name' => self::TOKEN_KEY])->first();
        if ($xu) {
            $this->access_token = $xu->value;
            $this->expire_time = $xu->expire_time;
            if (!empty($this->access_token) && $this->tokenValid()) {
                return $this->access_token;
            }

            // TODO 找一个打接口的库？
            $url = "https://api.weixin.qq.com/cgi-bin/token?" . http_build_query([
                    'grant_type' => 'client_credential',
                    'appid'      => env('WX_APP_ID'),
                    'secret'     => env('WX_SECRET'),
                ]);

            $response = json_decode(file_get_contents($url));
            if (!empty($response->errcode)) {
                // TODO log 一下错误
                return false;
            }

            if (!empty($response->access_token)) {
                $this->access_token = $response->access_token;
                $this->expire_time = time() + $response->expires_in - 300;

                MXu::updateOrInsert(['name' => self::TOKEN_KEY],
                    [
                        'value' => $this->access_token,
                        'create_time' => time(),
                        'expire_time' => $this->expire_time,
                    ]);

                return $this->access_token;
            }
        }
        return false;
    }

    private function tokenValid() {
        return $this->expire_time != -1 && $this->expire_time > time();
    }
}