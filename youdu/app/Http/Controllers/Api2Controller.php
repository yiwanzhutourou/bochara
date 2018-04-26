<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Exceptions\Exception;
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
        $url = "https://api.weixin.qq.com/wxa/getwxacode?access_token={$accessToken}";
        $data = [
            'path' => $page
        ];

        $options = [
            'http' => [
                'header'  => "Content-type:application/json",
                'method'  => 'POST',
                'content' => json_encode($data, true),
                'timeout' => 60
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === false) {
            throw new Exception(Exception::WEIXIN_RETURN_FAILED, '无法生成二维码');
        }

        return response($result, 200, ['Content-Type' => 'jpg']);
    }
}