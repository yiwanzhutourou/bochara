<?php

namespace App\Lib\Baidu;

use App\Lib\HttpClient\HttpClient;
use App\Utils\JsonUtils;

class BaiduPoiManager {

    /**
     * 经纬度获取省份\城市\区县
     *
     * @param $lat
     * @param $lng
     * @return array|mixed
     */
    public static function reversePoi($lat, $lng) {
        $url = "http://api.map.baidu.com/geocoder/v2/";
        $response = HttpClient::get($url, [
            'location' => $lat.','.$lng,
            'output'   => 'json',
            'pois'     => 1,
            'ak'       => env('BAIDU_MAP_AK'),
        ]);
        $location = json_decode($response);
        if ($location !== null && $location->status === 0) {
            if (!empty($location->result)) {
                $address = $location->result->addressComponent;
                if (!empty($address)) {
                    return JsonUtils::json_stringify([
                        'province' => $address->province,
                        'city'     => $address->city,
                        'district' => $address->district
                    ]);
                }
            }
        }
        return [];
    }
}