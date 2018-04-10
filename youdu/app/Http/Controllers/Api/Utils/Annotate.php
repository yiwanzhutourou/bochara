<?php

namespace App\Http\Controllers\Api\Utils;

use ReflectionMethod;

class Annotate {

    /**
     * @param $controller
     * @param $method
     * @return array
     * @throws \ReflectionException
     */
    public static function get($controller, $method) {
        $method = new ReflectionMethod($controller, $method);
        $comment = $method->getDocComment();
        preg_match_all('#^\s+\*\s+@(\w+)[ \t]*([^\r\n]*)\r*$#m', $comment, $matches);
        $return = [];
        foreach ($matches[1] as $key => $each_match) {
            if (!isset($return[$each_match])) {
                $return[$each_match] = [];
            }
            $return[$each_match][] = $matches[2][$key];
        }
        return $return;
    }
}