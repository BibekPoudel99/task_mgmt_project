<?php
class Request
{
	public static function method($method='post')
	{
		switch ($method){
			case 'post':
			return ($_SERVER['REQUEST_METHOD'] ==='POST' && !empty($_POST));
			
			case 'get':
			return ($_SERVER['REQUEST_METHOD'] ==='GET' && !empty($_GET));
			
			default:
			throw new Exception("Method Undefined.");			
		}
	}

	public static function post($field){
		return filter_input(INPUT_POST, $field, FILTER_SANITIZE_SPECIAL_CHARS);
	} 

	public static function get($field){
		return filter_input(INPUT_GET, $field, FILTER_SANITIZE_SPECIAL_CHARS);
	}

	// NEW METHOD - Add this to your existing Request class
	public static function has($field, $method = 'post') {
		return !empty(self::$method($field));
	}
}