<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Exceptions\Exception;
use App\Lib\HttpClient\HttpClient;
use App\Lib\Weixin\WxAccessTokenManager;
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
     * @throws Exception
     */
    public function getQRCode(Request $request) {
        $page = $request->input('page') ?? '/pages/index/index';

        $accessToken = WxAccessTokenManager::instance()->getAccessToken();
        if ($accessToken === false) {
            throw new Exception(Exception::WEIXIN_RETURN_FAILED, '无法生成二维码');
        }
        $url = "https://api.weixin.qq.com/wxa/getwxacode";
        $result = HttpClient::post($url, [
            'query'   => ['access_token' => $accessToken],
            'json'    => ['path' => $page],
            'timeout' => 60,
        ]);
        if (!$result) {
            throw new Exception(Exception::WEIXIN_RETURN_FAILED, '无法生成二维码');
        }

        return response($result, 200, ['Content-Type' => 'jpg']);
    }
}