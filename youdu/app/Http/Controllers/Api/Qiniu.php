<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Lib\Visitor;
use Qiniu\Auth;

class Qiniu extends ApiBase {

    /**
     * @return string
     * @throws Exceptions\Exception
     */
    public function getUploadToken() {
        Visitor::instance()->checkAuth();

        $auth = new Auth(env('QINIU_AK'), env('QINIU_AKS'));
        $bucket = 'bocha';
        // 生成上传Token
        $token = $auth->uploadToken($bucket);

        return $token;
    }
}