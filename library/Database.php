<?php
require_once 'Config.php';
class Database{

	private $_connect = null;
    private static $_instance = null;

    public function __construct(){
		$this->openConnection();
	}	

	private function openConnection()
	{
		try{
			
		    $this->_connect = new PDO('mysql:host='.Config::get('host').';dbname='.Config::get('name'),Config::get('username'),Config::get('password'));
		    $this->_connect->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	    }catch(PDOException $e){
		    die($e->getMessage());
	    }
	}
    public function getConnection()
    {
        if ($this->_connect === null) {
            $this->openConnection();
        }
        return $this->_connect;
    }
    public function closeConnection()
    {
        $this->_connect = null;
    }
	public function insert($table,$data=array()){

	if(empty($table) || empty($data)) return false;
	$fields = implode(',',array_keys($data));

	$sql = "INSERT INTO " . $table ." (" .$fields .") VALUES (?";
	
	for ($i=1; $i < count($data); $i++) { 
		$sql.= ',?';
	}

	$sql .= ')';

	$stmt = $this->_connect->prepare($sql);
	try{
		$stmt->execute(array_values($data));
		return $this->_connect->lastInsertId();
	}catch(PDOException $e){
		die($e->getMessage());
	}
}	

	public function select($table,$column="*",$criteria="",$value=array(),$clause=""){
	if (empty($table)) return false;
	
	$sql = "SELECT " . $column . " FROM " . $table;
	if (!empty($criteria)){
		$sql .= " WHERE " . $criteria;
	}
	if (!empty($clause)){
		$sql .= " " . $clause;
	}

	$stmt = $this->_connect->prepare($sql);

	try{
		if($stmt->execute($value)){
			return $stmt->fetchAll(PDO::FETCH_CLASS);
		}
		return false;			
	}catch(PDOException $e){
		die($e->getMessage());
	}
}

	public function count($table="",$criteria="",$value=array()){
	
	if (empty($table)) throw new Exception("Error Processing Request");
		$sql = "SELECT count(*) FROM " .$table;
	if(!empty($criteria) && !empty($value)){
		$sql .= " WHERE " . $criteria;
	}

	$stmt = $this->_connect->prepare($sql);

	try{
		if($stmt->execute($value)){
			$result = $stmt->fetchAll(PDO::FETCH_COLUMN);
			return $result[0];
		}
		return false;			
	}catch(PDOException $e){
		die($e->getMessage());
	}
}

	public function update($table,$data=array(),$criteria="",$value=array()){
	if(empty($table) || empty($data) || empty($criteria) || empty($value)) return false;
	$fields = implode('= ?,',array_keys($data));
	
	$sql = "UPDATE " . $table ." SET " . $fields;
	$sql .= " = ? WHERE " .$criteria;

	$fieldvalues = array_merge(array_values($data),$value);

	$stmt = $this->_connect->prepare($sql);

	try{
		$stmt->execute($fieldvalues);
		return true;
	}catch(PDOException $e){
		die($e->getMessage());
	}
	
}	

	public function delete($table,$criteria,$value=array()){
	if(empty($table) || empty($criteria)) return false;
	
	$sql = "DELETE FROM ". $table . " WHERE " . $criteria;		

	$stmt = $this->_connect->prepare($sql);

	try{
		$stmt->execute($value);
		return true;
	}catch(PDOException $e){
		die($e->getMessage());
	}
	
}	

    public static function instantiate(){
	if(!isset(self::$_instance)){
		return self::$_instance = new Database();
	}else{
		return self::$_instance;
	}
}	
}