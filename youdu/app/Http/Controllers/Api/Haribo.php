<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Lib\Visitor;

class Haribo extends ApiBase {

    /**
     * 给地下城用的测试接口
     *
     * 会清空用户的简介，所有地址，绑定的手机号，联系方式
     * @throws Exceptions\Exception
     */
    public function clearUser() {
        $user = Visitor::instance()->checkAuth(true);
        // clear all addresses
        $user->addresses()->delete();
        // clear info
        $user->info()->delete();

        // clear mobile, contact
        $user->mobile = '';
        $user->contact = '';
        $user->save();

        return 'ok';
    }
}