<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Goods_Hero_model extends CI_Model {

	private $table = NULL;
	public function __construct() 
	{
		parent::__construct();
		$this->table = $this->db->dbprefix('goods_hero');
	}

	public function save($params){
		if ( !$params OR ! is_array($params) ) {
			return FALSE;
		}
		
		$goods_id = intval($params['goods_id']);
		$sql = "SELECT * FROM `{$this->table}` WHERE `goods_id` = ?";
		$query = $this->db->query( $sql, array($goods_id));
		if ($query->num_rows()){
			$goods_hero = $query->row_array();
			$id = $goods_hero['id'];
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

