<?php

class Cookie {

    public static function set($name, $value, $expires = 3600, $path = "/", $domain = "", $secure = false, $httponly = true) {
        if (headers_sent()) {
            throw new Exception("Headers already sent");
        }
        setcookie($name, $value, time() + $expires, $path, $domain, $secure, $httponly);
    }

    public static function get($name) {
        return $_COOKIE[$name] ?? null;
    }

    public static function delete($name, $path = "/", $domain = "") {
        if (headers_sent()) {
            throw new Exception("Headers already sent");
        }
        setcookie($name, "", time() - 3600, $path, $domain);
        unset($_COOKIE[$name]);
    }

}
    