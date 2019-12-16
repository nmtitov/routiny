<?php

/**
 * @package Routiny
 * @copyright &copy; 2010
 * @author Nikita Titov <nmtitov@ya.ru>
 */
class RtHelper {
    const D = "|";
    const HTTP_METHODS = "OPTIONS|GET|HEAD|POST|PUT|PATCH|TRACE|LINK|UNLINK";
    const DECORATORS = "VIEW|XML|XSLT|JSON|TEXT";
    const TYPES = "TEXT|XML|JSON";

    /**
     * Check if provided function name is real function defined in system
     * 
     * @param string $func_name
     * @return bool
     */
    public static function check_string($func_name) {
        return is_string($func_name) && function_exists($func_name);
    }

    /**
     * Check if provided array (object (or class) and method (can be static))
     * ['object','method'] were defined in application
     * 
     * @param string $array
     * @return bool
     */
    public static function check_array($array) {
        if (!is_null($array)) {
            if (is_array($array)) {
                if (isset($array[0]) && isset($array[1])) {
                    return method_exists($array[0], $array[1]);
                }
            }
        }
    }

    /**
     * Check if provided function is anonymous function (instance of Closure)
     *
     * @param Closure $closure
     * @return bool
     */
    public static function check_closure($closure) {
        if (!is_null($closure)) {
            return is_object($closure) && $closure instanceof Closure;
        }
    }

    public static function checkHTTPMethod($method) {
        return in_array($method, explode(RtHelper::D, strtolower(RtHelper::HTTP_METHODS)));
    }

    public static function checkDecorator($decorator) {
        return in_array($decorator, explode(RtHelper::D, strtolower(RtHelper::DECORATORS)));
    }

    public static function checkType($type) {
        return in_array(strtolower($type), explode(RtHelper::D, strtolower(RtHelper::DECORATORS)));
    }

}
