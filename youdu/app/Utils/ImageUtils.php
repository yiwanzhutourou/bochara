<?php

namespace App\Utils;

class ImageUtils {
    public static function getOriginalImgUrl($url) {
        if (empty($url)) {
            return $url;
        }
        return $url . '?imageView2/0/format/jpg/q/75|imageslim';
    }
}