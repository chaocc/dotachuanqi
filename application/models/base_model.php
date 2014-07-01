<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//基础类
class Base_model extends CI_Model {
	private $table = NULL;
	
	public function init($prefix)
	{
		parent::__construct();
		$this->table = $this->db->dbprefix($prefix);
		return $this->table;
	}

		
	public function remove($id)
	{
		// 更新删除数据is_delete = 1
		$this->db->query("UPDATE `{$this->table}` set `is_delete`=1  WHERE `id`=?",array($id));
		$ret = $this->db->affected_rows();
		return $ret;
	}
	
	public function recover($id)
	{
		$this->db->query("UPDATE `{$this->table}` set `is_delete`=0  WHERE `id`=?",array($id));
		$ret = $this->db->affected_rows();
		return $ret;
	}
	

	public function get_list($page = 1,$is_delete=0)
	{
		$page = intval($page);
		$page = $page > 0 ? $page : 1;
		
		$total = $this->get_total($is_delete);
		$page_size = PAGESIZE;
		$max_page =  max(1, ceil($total / $page_size));
		$page = min(max(1, $page), $max_page);
		$start = ($page - 1) * $page_size;
		
		$list = array('total' => $total, 'curpage' => $page, 'maxpage' => $max_page, 'items' => array() );
		$where = 'WHERE  `is_delete`='.$is_delete;
		$order = 'ORDER BY `id` DESC';
		
		if ($total) {
			$query = $this->db->query("SELECT * FROM `{$this->table}` {$where} {$order} LIMIT ?, ? ", array($start, $page_size));
			foreach ($query->result_array() as $row) {
				$list['items'][] = $row;
			}
		}
		
		return $list;
	}
	

	public function get_total($is_delete=0)
	{
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `{$this->table}` where is_delete='{$is_delete}'");
		$row = $query->row_array();
		return $row ? $row['total'] : 0;
	}
	
		public function get_one($id)
	{
		$id = intval($id);
		if ($id <= 0) {
			return FALSE;
		}
		$sql = "SELECT * FROM `{$this->table}` WHERE `id` = ?";
		$query = $this->db->query( $sql, array($id));
		if ( ! $query->num_rows()) {
			return FALSE;
		}
		$result = $query->row_array();
		return $result;
	}
	
		public function save($params)
		{
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
			$result = $query->row_array();
			$id = $result['id'];
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
