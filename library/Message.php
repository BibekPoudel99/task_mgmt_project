<?php
class Message{
	
	public static function displayMessage(){
	
		if(isset($_SESSION['success'])){
			$class = 'alert-success';
			$message = $_SESSION['success'];
			unset ($_SESSION['success']);
		}

		if(isset($_SESSION['error'])){
			$class = 'alert-danger';
			$message = $_SESSION['error'];
			unset ($_SESSION['error']);
		}

		// You can also add warning and info messages
		if(isset($_SESSION['warning'])){
			$class = 'alert-warning';
			$message = $_SESSION['warning'];
			unset ($_SESSION['warning']);
		}

		if(isset($_SESSION['info'])){
			$class = 'alert-info';
			$message = $_SESSION['info'];
			unset ($_SESSION['info']);
		}

		$output = '';
		if (isset($message)){
			// Updated code with dismissible alert
			$output .= "<div class='alert ".$class." alert-dismissible'>";
			$output .= "<button type='button' class='close' data-dismiss='alert'>&times;</button>";
			$output .= $message;
			$output .= "</div>";
		}
		return $output;
	}
}