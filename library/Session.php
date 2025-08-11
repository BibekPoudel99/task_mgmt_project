<?php
class Session
{
	public static function put($key,$value){
		if(!isset($key)) return false;
		return $_SESSION[$key] = $value;
	}	

	public static function get($key){
		if(!isset($key)) return false;
		if(self::check($key)){
			return $_SESSION[$key];
		}
		return '';
	}

	public static function check($key){
		return isset($_SESSION[$key]);
	}

	public static function delete($key){
		if(!isset($key)) return false;
		if(self::check($key)){
			unset($_SESSION[$key]);
		}
		return true;
	}	

    // Add method to destroy entire session (for logout)
    public static function destroy() {
        session_destroy();
        return true;
    }

    // Add method to get with default value
    public static function getWithDefault($key, $default = null) {
        return self::check($key) ? self::get($key) : $default;
    }
}