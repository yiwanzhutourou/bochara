<?php

namespace App\Lib;

class SentryHelper {

    public static function report(\Exception $e) {
        if (env('APP_ENV') === 'production') {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        }
    }
}