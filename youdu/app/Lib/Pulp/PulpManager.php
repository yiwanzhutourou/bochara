<?php

namespace App\Lib\Pulp;

use App\Models\MCardPulp;

class PulpManager {

    /**
     * @param string $picUrl
     * @param array  $params
     * @return bool
     */
    public static function checkPulp($picUrl, $params) {
        if (empty($picUrl)) {
            return false;
        }
        $url = $picUrl . '?pulp';
        $response = file_get_contents($url);
        $pulp = json_decode($response);
        // 没解出数据认为是正常的
        if (!$pulp || !$pulp->pulp) {
            return false;
        }

        if ($pulp->code === 0
            && $pulp->pulp->label === 2) {
            // 存一下 log
            $log = array_merge([
                'pic_url'     => $picUrl,
                'create_time' => time(),
                'pulp_rate'   => $pulp->pulp->rate,
                'pulp_label'  => $pulp->pulp->label,
                'pulp_review' => $pulp->pulp->review,
            ], $params);
            MCardPulp::create($log);
            return true;
        }

        return false;
    }
}