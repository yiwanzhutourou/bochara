<?php
/**
 * Created by PhpStorm.
 * User: bx
 * Date: 2018/4/9
 * Time: 下午10:24
 */

namespace App\Utils;

use Illuminate\Http\Response;

class ErrorUtils {
    public static function invalidParameterResponseGenerator(string $hint): Response {
        return response($hint, 422);
    }
}