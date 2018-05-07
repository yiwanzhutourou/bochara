<?php

namespace App\Lib\HttpClient;

use App\Lib\SentryHelper;

class HttpClient {

    private static function make() {
        return new \GuzzleHttp\Client();
    }

    public static function get(string $uri = '',
                               array $params = []) {
        try {
            $response = self::make()->get($uri, ['query' => $params]);
            if ($response->getStatusCode() !== 200) {
                return null;
            }
            return $response->getBody();
        } catch (\Exception $e) {
            SentryHelper::report($e);
            return null;
        }
    }

    public static function post(string $uri = '',
                                array $options = []) {
        try {
            $response = self::make()->post($uri, $options);
            if ($response->getStatusCode() !== 200) {
                return null;
            }
            return $response->getBody();
        } catch (\Exception $e) {
            SentryHelper::report($e);
            return null;
        }
    }
}