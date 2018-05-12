<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Exceptions\ApiException;
use App\Http\Controllers\Api\Exceptions\Exception;
use App\Lib\HttpClient\HttpClient;
use App\Lib\SentryHelper;
use App\Lib\Weixin\WxAccessTokenManager;
use App\Utils\ErrorUtils;
use Illuminate\Http\Request;

class Api2Controller extends Controller {

    public function __invoke(Request $request, string $action = 'index') {
        if (!method_exists($this, $action)) {
            $action = 'index';
        }
        return $this->{$action}($request);
    }

    public function index(Request $request) {
        abort(404);
    }


    /**
     * @param Request $request
     * @return mixed
     */
    public function getQRCode(Request $request) {
        $page = $request->input('page') ?? '/pages/index/index';

        $accessToken = WxAccessTokenManager::instance()->getAccessToken();
        if ($accessToken === false) {
            return ErrorUtils::errorResponse('无法生成二维码', Exception::WEIXIN_RETURN_FAILED);
        }
        $result = $this->getQRCodeFromWeixin($accessToken, $page);
        if (!$result) {
            return ErrorUtils::errorResponse('无法生成二维码', Exception::WEIXIN_RETURN_FAILED);
        }

        $json = json_decode($result);
        if ($json && (!empty($json->errcode) || $json->errcode > 0)) {
            // 线上环境清空token重试一次
            if (env('APP_ENV') === 'production') {
                $accessToken = WxAccessTokenManager::instance()->getAccessToken(true);
                if ($accessToken === false) {
                    return ErrorUtils::errorResponse('无法生成二维码', Exception::WEIXIN_RETURN_FAILED);
                }
                $result = $this->getQRCodeFromWeixin($accessToken, $page);
                $json = json_decode($result);
                if ($json && !empty($json->errcode) || $json->errcode > 0) {
                    SentryHelper::report(new ApiException($json->errcode, $result));
                    return ErrorUtils::errorResponse('无法生成二维码', Exception::WEIXIN_RETURN_FAILED);
                }
            }
            return ErrorUtils::errorResponse('无法生成二维码', Exception::WEIXIN_RETURN_FAILED);
        }

        return response($result, 200, ['Content-Type' => 'jpg']);
    }

    private function getQRCodeFromWeixin($accessToken, $page) {
        $url = "https://api.weixin.qq.com/wxa/getwxacode";
        return HttpClient::post($url, [
            'query'   => ['access_token' => $accessToken],
            'json'    => ['path' => $page],
            'timeout' => 60,
        ]);
    }
}