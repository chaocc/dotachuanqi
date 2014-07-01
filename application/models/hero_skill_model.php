<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Hero_Skill_model extends CI_Model {

	private $table = NULL;
	public function __construct() 
	{
		parent::__construct();
		$this->table = $this->db->dbprefix('hero_skill');
		$this->load->model('skill_model');
		$this->load->model('hero_model');
	}
	public function save($params){
		if ( !$params OR ! is_array($params) ) {
			return FALSE;
		}
		
		$hero_id = intval($params['hero_id']);
		$sql = "SELECT * FROM `{$this->table}` WHERE `hero_id` = ?";
		$query = $this->db->query( $sql, array($hero_id));
		if ($query->num_rows()){
			$hero_skill = $query->row_array();
			$id = $hero_skill['id'];
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
	public function get($hero_id)
	{
			$sql = "SELECT * FROM `{$this->table}` WHERE `hero_id`=?";
			$query = $this->db->query($sql,array($hero_id));
			$hero_skill = $query->row_array();
			$skill_id =explode(',',$hero_skill['skill_id']);
			$skill_array = array();
			foreach($skill_id as $key=> $v)
			{
				$skillSql = "SELECT * FROM `d_skill` WHERE `id`=$v";
				$query = $this->db->query($skillSql);
				$skill = $query->row_array();
				array_push($skill_array,$skill);
			}
			return $skill_array;
	}
	public function get_by_skill($skill_id)
	{
		$sql = "SELECT * FROM `{$this->table}` WHERE  FIND_IN_SET($skill_id,`skill_id`)>0";
		$query = $this->db->query($sql);
		$hero_skill = $query->row_array();
		$hero = $this->hero_model->get_hero($hero_skill['hero_id']);
		return $hero;
	}
	public function save_by_hero($hero_id,$skill_id)
	{
		$sql = "SELECT * FROM `{$this->table}` WHERE `hero_id`=?";
		$query = $this->db->query($sql,array($hero_id));
		$hero_skill = $query->row_array();
		$skill_id = $hero_skill['skill_id'].','.$skill_id;
		$sql = "UPDATE `{$this->table}` set `skill_id`='{$skill_id}' WHERE hero_id='{$hero_id}'";
		$this->db->query($sql);
		return $this->db->affected_rows();
	}
}

