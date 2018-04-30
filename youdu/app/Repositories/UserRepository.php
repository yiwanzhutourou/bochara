<?php

namespace App\Repositories;

use App\Models\MFollow;
use App\Models\MUser;
use App\Models\MUserAddress;
use App\Utils\CommonUtils;

class UserRepository {

    /**
     * @param mixed $wxOpenId
     * @return MUser|null
     */
    public static function findByWxOpenId($wxOpenId) {
        return MUser::where(['wechat_open_id' => $wxOpenId])->first();
    }

    public static function getUserAddresses(MUser $user,
                                            $latitude = -1, $longitude = -1,
                                            $sortByDistance = true) {
        $hasPoi = $latitude !== -1 && $longitude !== -1;
        $addressList = $user->addresses()->get();
        $addresses = [];
        /** @var MUserAddress $address */
        foreach ($addressList as $address) {
            $addressItem = [
                'id'        => $address->id,
                'latitude'  => $address->latitude,
                'longitude' => $address->longitude,
                'name'      => $address->address,
                'detail'    => $address->detail,
                'city'      => json_decode($address->city),
            ];

            if ($hasPoi) {
                $distance = CommonUtils::getDistance($latitude, $longitude,
                    $address->latitude, $address->longitude);
                $addressItem['distance'] = $distance;
            }
            $addresses[] = $addressItem;
        }
        if ($hasPoi && $sortByDistance) {
            usort($addresses, function ($a, $b) {
                return ($a['distance'] > $b['distance']) ? 1 : -1;
            });
        }
        return $addresses;
    }

    public static function isFollowing($from, $to) {
        return MFollow::where(['from_id' => $from, 'to_id' => $to])->count() > 0;
    }

    /**
     * TODO for test use
     *
     * @return MUser|null
     */
    public static function testUser() {
        return MUser::find(34);
    }
}