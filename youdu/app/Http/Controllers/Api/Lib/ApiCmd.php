<?php

namespace App\Http\Controllers\Api\Lib;

use App\Http\Controllers\Api\Exceptions\Exception;
use App\Http\Controllers\Api\Utils\ApiUtils;

class ApiCmd {

    private $name, $requestData, $method;

    public $result = null;
    public static $resultCacheTime = 0;

    /**
     * ApiCmd constructor.
     *
     * @param $action
     * @param $requestData
     * @param $method
     * @throws Exception
     */
    public function __construct($action, $requestData, $method) {
        $this->parseApiName($action);
        $this->requestData = $requestData;
        $this->method = $method;
    }

    /**
     * @param $action
     * @throws Exception
     */
    private function parseApiName($action) {
        if (count(explode('.', $action)) == 2) {
            $this->name = $action;
            return;
        }
        throw new Exception(Exception::INVALID_COMMAND);
    }

    /**
     * @return mixed|null
     * @throws Exception
     * @throws \Exception
     */
    public function run() {
        self::setCacheTime(0);
        $this->result = $this->runApi();
        return $this->result;
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws \Exception
     */
    private function runApi() {
        $callContext = [
            'method' => $this->method,
        ];
        return ApiUtils::api($this->name, $this->requestData, $callContext);
    }

    public static function setCacheTime($seconds) {
        self::$resultCacheTime = $seconds;
    }
}