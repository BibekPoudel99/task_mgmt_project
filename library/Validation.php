<?php
class Validation
{
	private $_errors = [];
	private $_db;

	function __construct()
	{
		$this->_db = Database::instantiate();
	}

	private function setErrors($field,$message){
		$this->_errors[$field] = $message;
	}

	public function getErrors(){
		return $this->_errors;
	}

	public function isValid(){
		if(empty($this->_errors)) return true;
		return false;
	}

	public static function displayErrors($key,$class = ''){
		$errors = session::get($key);
		$output = "";
		if(!empty($errors)){
			foreach ($errors as $error){
				$output .= "<div class='alert alert-danger'>";
				$output .= $error;
				$output .= "</div>";
			}
			session::delete($key);
			return $output;
		}
		return '';
	}
public function validate($validationRules=array() ){
	
	if(empty($validationRules)) return false;
	
	foreach ($validationRules as $field => $rules){
	
		if(isset($_POST[$field])){
	
			foreach ($rules as $rule => $value){
	
				if($rule === 'required' && Request::post($field) === ''){
					$this->setErrors($field,$rules['label'] . ' can\'t be empty');
				}else if (Request::post($field) !== ''){
					
					switch($rule){
						
						case 'minlength':
						if(strlen(Request::post($field)) < $value){
							$this->setErrors($field,$rules['label'] . ' can\'t be less than ' . $value . ' characters');
						}
						break;
							
						case 'maxlength':
						if(strlen(Request::post($field)) > $value){
							$this->setErrors($field,$rules['label'] . ' can\'t be greater than ' . $value . ' characters');
						}
						break;
							
						case 'email':
						if(!filter_var(Request::post($field), FILTER_VALIDATE_EMAIL)){
							$this->setErrors($field,$rules['label'] . ' is not a valid email address');
						}
						break;
							
						case 'unique':
						$dbArray = explode('.',$value);
	
						if(count($dbArray) < 2) throw new Exception("Unique Requires table and column name");
						$tableName = $dbArray[0];
						$columnName = $dbArray[1];

						if(count($dbArray) > 3){
							$key = $dbArray[2];
							$value = $dbArray[3];

							$dataCount = $this->_db->count($tableName,$columnName.' = ? AND '.$key.' != ?',array(Request::post($field),$value));
						}else{
							$dataCount = $this->_db->count($tableName,$columnName.'=?',array(Request::post($field)));	
						}
	
						if($dataCount > 0){
							$this->setErrors($field,$rules['label'] . ' already exists');
						}
						break;

						case 'matches':
						if(Request::post($field) !== Request::post($rules['matches'])){
							$this->setErrors($field,$rules['label'] . ' doesn\'t match with the Password');
						}
					}
				}
			}
		}	
	}
}	
}