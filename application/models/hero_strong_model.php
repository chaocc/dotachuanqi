<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Hero_Strong_model extends Base_model {

	private $table = NULL;
	public function __construct() 
	{
		$this->load->model('hero_model');
		$this->table=parent::init('hero_strong');
	}
	
	public function select_all($params)
	{
		$sql = "select *  from `{$this->table}` order by `id`";
		$query = $this->db->query($sql);
		$hero_strong = $query->result();
		foreach($hero_strong as $key =>&$v)
		{
			$hero=$this->hero_model->get_hero($v->id);
			$v->hero_name= $hero['hero_name'];
		}
		$pagenum = $params['pagenum'];
		$pagesize = $params['pagesize'];
		$begin = ($pagenum-1)*$pagesize;
		$hero_strong = array_slice($hero_strong,$begin,$pagesize);
		return $hero_strong;
	}

	public function is_update($hero_strong_timestamp)
	{
		$sql = "SELECT COUNT(*) as count FROM `{$this->table}` WHERE UNIX_TIMESTAMP(`create_time`)>$hero_strong_timestamp";
		$query = $this->db->query($sql);
		$hero_stong_count = $query->row_array();
		return $hero_stong_count['count'];
	}
	public function save($params){
		if ( !$params OR ! is_array($params) ) {
			return FALSE;
		}
		$hero_id = intval($params['hero_id']);
		$sql = "SELECT * FROM `{$this->table}` WHERE `hero_id` = ?";
		$query = $this->db->query( $sql, array($hero_id));
		if ($query->num_rows()) {
			$hero_strong= $query->row_array();
			$id = $hero_strong['id'];
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
