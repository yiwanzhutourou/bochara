<?php
/**
 * Created by PhpStorm.
 * User: bx
 * Date: 2018/4/10
 * Time: 下午1:40
 */

namespace App\Http\Controllers\Api\Exceptions;

class NeedRedirectException extends \Exception {

    /**
     * @var string
     */
    public $url;

    /**
     * @var int
     */
    public $status;

    public function __construct($redirectUrl, $status = 302, $message = '') {
        // 很多场景下，跳转前要跟用户说明情况，需要设置message
        parent::__construct($message);

        $this->url = $redirectUrl;
        $this->status = $status;
    }
}