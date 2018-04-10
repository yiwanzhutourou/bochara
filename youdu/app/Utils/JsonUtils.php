<?php

namespace App\Utils;

class JsonUtils {
    public static function json_stringify($data) {
        $result = @json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (json_last_error() === JSON_ERROR_NONE) {
            return str_replace('</script>', '<\/script>', $result);
        }

        return 'null';
    }
}