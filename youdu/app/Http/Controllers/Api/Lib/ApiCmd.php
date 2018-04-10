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
     * @param $uri
     * @param $requestData
     * @param $method
     * @throws Exception
     */
    public function __construct($uri, $requestData, $method) {
        $this->parseApiName($uri);
        $this->requestData = $requestData;
        $this->method = $method;
    }

    /**
     * @param $uri
     * @throws Exception
     */
    private function parseApiName($uri) {
        $paths = array_slice(explode('/', ltrim($uri, '/')), 1);
        if (count($paths) == 1) { //uri /api/User.addBook/
            if (count(explode('.', $paths[0])) == 2) {
                $name = $paths[0];
                $this->name = $name;
                return;
            }
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