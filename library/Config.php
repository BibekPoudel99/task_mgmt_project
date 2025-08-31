<?php
require_once __DIR__ . '/../config/config.php';
class Config {
    public static function get($getString) {
        if (empty($getString)) return false;
        $globals = $GLOBALS['configs']['database'];

        if(isset($globals[$getString])) {
            return $globals[$getString];
        } else {
            return '';
        }
    }

}