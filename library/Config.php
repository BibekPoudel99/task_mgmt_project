<?php
class Config {
    public static function get($getString) {
        if (empty($getString)) return false;
        $globals = $GLOBALS['database'];
        
        if(isset($globals[$getString])) {
            return $globals[$getString];
        } else {
            return '';
        }
    }

}