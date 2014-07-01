<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Hero_model extends Base_model {

	private $table = NULL;
	public function __construct() 
	{
		$this->table=parent::init('hero');
	}

	public function save_hero($params){
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
		if ($query->num_rows()) {
			$hero = $query->row_array();
			$id = $hero['id'];
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
	public function select_all()
	{
		$sql = "SELECT * FROM `{$this->table}` order by id asc";
		$query = $this->db->query( $sql);
		if ( ! $query->num_rows()) {
			return FALSE;
		}
		$hero = $query->result();
		return $hero;
	}
	
	public function get_hero($hero_id)
	{
		$hero_id = intval($hero_id);
		if ($hero_id <= 0) {
			return FALSE;
		}
		$sql = "SELECT * FROM `{$this->table}` WHERE `id` = ?";
		$query = $this->db->query( $sql, array($hero_id));
		if ( ! $query->num_rows()) {
			return FALSE;
		}
		$hero = $query->row_array();
		return $hero;
	}
	public function select_hero($params)
	{
		$where = '1=1';
		$params['place'] = addslashes($params['place']);
		$params['class'] = addslashes($params['class']);
		if($params['place'] && !$params['class'])
		{
			$where = "where place = '{$params['place']}'";
		}else if(!$params['place'] && $params['class'])
		{
			$where = "where  class ='{$params['class']}'";
		}else if($params['place'] && $params['class']){
			$where = "where place = '{$params['place']}' and class ='{$params['class']}'";
		}
		$sql = "select `id`,`hero_img` from `{$this->table}`".$where.' order by `id`';
		$query = $this->db->query($sql);
		$hero = $query->result();
		$pagenum = $params['pagenum'];
		$pagesize = $params['pagesize'];
		$begin = ($pagenum-1)*$pagesize;
		$hero = array_slice($hero,$begin,$pagesize);
		return $hero;
	}
	public function is_update($hero_timestamp)
	{
		$sql = "SELECT COUNT(*) as count FROM `{$this->table}` WHERE UNIX_TIMESTAMP(`create_time`)>$hero_timestamp";
		$query = $this->db->query($sql);
		$heroCount = $query->row_array();
		return $heroCount['count'];
	}
	public function get_hero_id($hero_name)
	{
		$sql = "SELECT id  FROM `{$this->table}` WHERE hero_name='{$hero_name}'";
		$query = $this->db->query($sql);
		$hero = $query->row_array();
		return $hero['id'];
	
	}
	
	public function get($hero_name)
	{
			$sql = "SELECT * FROM `{$this->table}` where hero_name like '%$hero_name%'";
			$query = $this->db->query($sql);
			$goods = $query->result();
			$rs=array();
			foreach($goods as $k=>$v)
			{
				$rs[$k]['id'] = $v->id;
				$rs[$k]['hero_name'] = $v->hero_name;
			}
			return $rs;
	}

	
}

