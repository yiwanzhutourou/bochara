<?php

namespace App\Http\Controllers\Api;

use App\Models\MUser;
use App\Models\MUserAddress;
use App\Repositories\UserRepository;

class Map extends ApiBase {

    // 每一度纬度和经度对应的距离（公里）
    const LAT_DISTANCE = 111.7;
    const LNG_DISTANCE = 85.567;

    // 需要进行搜索的附近距离，默认值会根据数据多少来调整
    const NEAR_DISTANCE = 10;

    public function getMarkers() {
        $userAddresses = MUserAddress::all();
        $markers = [];
        foreach ($userAddresses as $userAddress) {
            $markers[] = [
                'id'        => $userAddress->user_id,
                'latitude'  => $userAddress->latitude,
                'longitude' => $userAddress->longitude,
            ];
        }
        return $markers;
    }

    /**
     * 根据经纬度获取周围的一定公里以内的点
     * @param $lat
     * @param $lng
     * @param int $distance
     * @return array
     */
    public function getMarkersNearBy($lat = 31.181471,
                                     $lng = 121.438378,
                                     $distance = self::NEAR_DISTANCE) {

        $latOffset = $distance / self::LAT_DISTANCE;
        $lngOffset = $distance / self::LNG_DISTANCE;

        $minLat = $lat - $latOffset;
        $maxLat = $lat + $latOffset;
        $minLng = $lng - $lngOffset;
        $maxLng = $lng + $lngOffset;

        $userAddresses = MUserAddress::where('latitude', '>', $minLat)
            ->where('latitude', '<', $maxLat)
            ->where('longitude', '>', $minLng)
            ->where('longitude', '<', $maxLng)
            ->join('bocha_user',
                'bocha_user_address.user_id', '=', 'bocha_user.id')
            ->get();
        $markers = [];
        foreach ($userAddresses as $userAddress) {
            $markers[] = [
                'id'        => $userAddress->user_id,
                'title'     => $userAddress->nickname,
                'latitude'  => $userAddress->latitude,
                'longitude' => $userAddress->longitude,
            ];
        }
        return $markers;
    }

    /**
     * 获取一组、或者一个用户ID的相关信息
     * @param $userIds
     * @return array
     */
    public function getUserAddresses($userIds) {
        $userIdArray = explode(',', $userIds);
        $result = [];
        foreach ($userIdArray as $userId) {
            $uid = trim($userId);
            if (!empty($uid)) {
                /** @var MUser $user */
                $user = MUser::find($userId);
                if (!$user) {
                    continue;
                }
                /** @var MUserAddress $address */
                $addresses = UserRepository::getUserAddresses($user);
                $bookCount = $user->bookCount();
                $address = empty($addresses) ? [] : $addresses[0];
                $result[] = [
                    'id'        => $userId,
                    'nickname'  => $user->nickname,
                    'avatar'    => $user->avatar,
                    'address'   => $address,
                    'bookCount' => $bookCount
                ];
            }
        }
        return $result;
    }
}