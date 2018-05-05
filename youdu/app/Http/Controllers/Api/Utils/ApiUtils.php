<?php

namespace App\Http\Controllers\Api\Utils;

use App\Http\Controllers\Api\ApiBase;
use App\Http\Controllers\Api\Exceptions\ApiException;
use App\Http\Controllers\Api\Exceptions\Exception;

class ApiUtils {
    /**
     * @param      $api_name
     * @param      $data
     * @param null $callContext
     * @return mixed
     * @throws ApiException
     * @throws \Exception
     */
    public static function api($api_name, $data, $callContext = null) {
        list($className, $method) = explode('.', trim($api_name));
        $className = ucfirst($className);
        $class = "App\\Http\\Controllers\\Api\\{$className}";

        // check class
        $api = self::apiCheckClass($class);
        if (!$api) throw new ApiException(Exception::INVALID_COMMAND, "非api接口，不能调用：{$className}");

        // check method
        $method = self::apiCheckMethod($api, $method);
        if (!$method) throw new ApiException(Exception::INVALID_COMMAND, "api接口不存在此方法，不能调用：{$className}.{$method}");

        self::apiCheckRequestMethod($api, $method, $callContext);
        $callPars = self::apiCheckParams($api, $method, $data);

        $result = call_user_func_array([$api, $method], $callPars);
        return $result;

    }

    private static function apiCheckClass($className) {
        if (!class_exists($className)) return false;
        $api = new $className();
        if (!$api instanceof ApiBase) return false;
        return $api;
    }

    private static function apiCheckMethod($api, $method) {
        if (!is_callable([$api, $method])) return false;
        return $method;
    }

    /**
     * @param      $api
     * @param      $method
     * @param null $callContext
     * @return bool
     * @throws \Exception
     * @throws \ReflectionException
     */
    private static function apiCheckRequestMethod($api, $method, $callContext = null) {
        if (isset($callContext['method'])) {
            $annotates = Annotate::get($api, $method);
            if (!empty($annotates['method'])) {
                $expectMethod = strtoupper($annotates['method'][0]);
                if (strtoupper($callContext['method']) != $expectMethod) {
                    throw new ApiException("api接口" . get_class($api) . ".{$method}请使用{$expectMethod}请求");
                }
            }
        }
        return true;
    }

    /**
     * @param $api
     * @param $method
     * @param $data
     * @return array
     * @throws \ReflectionException
     * @throws ApiException
     */
    private static function apiCheckParams($api, $method, $data) {
        $pars = (new \ReflectionMethod($api, $method))->getParameters();
        $callPars = [];
        foreach ($pars as $eachPar) {
            $key = $eachPar->getName();
            if (isset($data[$key])) {
                $callPars[] = $data[$key];
            } elseif ($key == 'otherArgs') {
                $callPars[] = &$data;
            } elseif ($eachPar->isDefaultValueAvailable()) {
                $callPars[] = $eachPar->getDefaultValue();
            } else {
                throw new ApiException(Exception::PARAMETERS_MISSING, '缺少参数：' . $key);
            }
            unset($data[$key]);
        }
        return $callPars;
    }

}