<?php
class Token
{
	private static function generate(){
		return session::put( 'csrf_token', md5(uniqid()) );
	}

	public static function input(){
		return "<input type='hidden' name='csrf_token' value='".self::generate()."'>";
	}

	public static function check($token){
		if(session::get('csrf_token') === $token){
			session::delete('csrf_token');
			return true;
		}
		return false;
	}
}	