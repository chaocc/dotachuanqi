<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Goods_model extends Base_model {

	private $table = NULL;
	public function __construct() 
	{
		$this->table=parent::init('goods');
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
			$goods = $query->row_array();
			$id = $goods['id'];
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
		public function select_goods($params)
	{
		$where = 'where 1=1';
		if(isset($params['color']) && $params['color'])
		{
			$params['color'] = addslashes($params['color']);
		}else{
			$params['color'] ='';
		}
		if(isset($params['type']) && $params['type'])
		{
			$params['type'] = addslashes($params['type']);
		}else{
			$params['type']='';
		}
		
		if($params['color'] && !$params['type'])
		{
			$where .= " AND  color = '{$params['color']}'";
		}else if($params['type'] && !$params['color'])
		{
			$where .= " AND  type ='{$params['type']}'";
		}else if($params['color'] && $params['type']){
			$where .= " AND color = '{$params['color']}' and type ='{$params['type']}'";
		}
		
		$sql = "select `id`,`goods_img`,`goods_name`,`description`  from `{$this->table}`".$where.' order by `id`';
		$query = $this->db->query($sql);
		$goods = $query->result();
		$pagenum = intval($params['pagenum']);
		$pagesize =intval($params['pagesize']);
		$begin = ($pagenum-1)*$pagesize;
		$goods = array_slice($goods,$begin,$pagesize);
		return $goods;
	}

	public function is_update($goods_timestamp)
	{
		$sql = "SELECT COUNT(*) as count FROM `{$this->table}` WHERE UNIX_TIMESTAMP(`create_time`)>$goods_timestamp";
		$query = $this->db->query($sql);
		$goods_count = $query->row_array();
		return $goods_count['count'];
	}
	
	public function get($goods_name)
	{
		$sql = "SELECT * FROM `{$this->table}` where goods_name like '%$goods_name%'";
		$query = $this->db->query($sql);
		$goods = $query->result();
		$rs=array();
		foreach($goods as $k=>$v)
		{
			$rs[$k]['id'] = $v->id;
			$rs[$k]['goods_name'] = $v->goods_name;
		}
		return $rs;
	}

}

