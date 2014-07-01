<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Skill_model extends Base_model {

	private $table = NULL;
	public function __construct() 
	{
		$this->table=parent::init('skill');
	}

	public function save($params){
		if ( !$params OR ! is_array($params) ) {
			return FALSE;
		}
		
		if(isset($params['id']))
		{
			$id = intval($params['id']);
		}else{
			$id=0;
		}
		$sql = "SELECT * FROM `{$this->table}` WHERE `id` = ?";
		$query = $this->db->query( $sql, array($id));
		if ($query->num_rows()){
			$skill = $query->row_array();
			$id = $skill['id'];
		}else{
			$id=0;
		}
		$sql = ($id > 0 ? 'UPDATE ' : 'INSERT INTO ')." `{$this->table}` SET ";
		$db_values = array();
		foreach ($params as $db_field => $db_value)
		{
			$db_field = trim(strip_tags($db_field));
			if ( ! $db_field) {
				continue;
			}	
			$sql .= "`{$db_field}` = ?, ";
			$db_values[] = is_numeric($db_value) ? $db_value : addslashes($db_value);
		}
		$sql = trim( rtrim( trim($sql) , ',') );
		
		if ($id > 0) {
			$sql .= ' WHERE `id` = ? ';
			$db_values[] = $id;
		}

		$this->db->query($sql, $db_values);
		return $id ? $this->db->affected_rows() : $this->db->insert_id();
	}
}

