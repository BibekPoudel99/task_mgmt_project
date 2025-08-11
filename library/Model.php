<?php
abstract class Model
{

	protected $table, $key, $field, $limit, $offset;
	private $_db;

	protected function __construct()
	{		
		$this->_db = Database::instantiate();
	}

	protected function save($data=array(),$key=null){
		
		if(empty($data)) throw new Exception("Columns and Values not set");

		if(!isset($key)){
			return $this->_db->insert($this->table,$data);	
		}

		return $this->_db->update($this->table,$data,$this->key.'=?',array($key));	
	}

	protected function get($id=""){
		
		if(empty($id)){
			
			if(!empty($this->limit)){
				return $this->_db->select($this->table,$this->field,'',array(),'LIMIT '.$this->limit.' OFFSET ' . $this->offset );	
			}
			return $this->_db->select($this->table,$this->field);
		}

		$result = $this->_db->select($this->table,$this->field,$this->key.'=?',array($id));
		
		if(count($result)){
			return $result[0];
		}
		return false;
	}

	protected function delete($id){
		return $this->_db->delete($this->table,$this->key.' = ?',array($id));
	}

	protected function multipleDelete($data){
    if(empty($data)) return false;
    $placeholders = str_repeat('?,', count($data) - 1) . '?';
    return $this->_db->delete($this->table, $this->key.' IN ('.$placeholders.')', $data);
	}
	protected function countRow(){
		return $this->_db->count($this->table);
	}

	public function getBy($criteria,$value=array(),$single=false){
		
		if(empty($criteria)) return false;

		$result = $this->_db->select($this->table,$this->field,$criteria,$value);

		if(empty($result)) return $result;
		
		if($single === true){
			return $result[0];
		}
		return $result;
	}
}

//This code creates an Abstract Model class that follows the Active Record pattern. It's a base class for creating specific model classes (like Task, User, Category).