<?php
class Redirect
{
	public static function to($path=""){
		if(empty($path)) return false;
		if(strpos($path,'/')){
			$path = explode('/',$path); 
			$redirectPath = HTTP.$path[0] .  '/main.php?page=' .$path[1];			
			header('Location: '.$redirectPath); 
			exit();
		}else{
			header('Location: '.$path);
			exit();
		}
		
	}	
}