<?php
class Hash
{
	public static function make($string=""){
		return password_hash($string,PASSWORD_DEFAULT);
	}

	public static function verify($string, $hash){
		return password_verify($string,$hash);
	}
}	