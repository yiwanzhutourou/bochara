<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Lib\Visitor;

class User extends ApiBase {

    /**
     * @param $isbn
     * @throws Exceptions\Exception
     */
    public function removeBook($isbn) {
        Visitor::instance()->checkAuth();

        // TODO
    }
}