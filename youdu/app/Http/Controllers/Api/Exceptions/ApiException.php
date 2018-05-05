<?php

namespace App\Http\Controllers\Api\Exceptions;

class ApiException extends \Exception {

    public $ext;

    public function httpCode() {
        return 404;
    }

    public function __construct($code = 0, $message = "", $ext = null) {
        $this->ext = $ext;
        parent::__construct($message, $code);
    }

    public function output() {
        return [
            'error' => $this->getCode(),
            'message' => $this->getMessage(),
            'ext' => $this->ext,
        ];
    }
}