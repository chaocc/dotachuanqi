<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Hero_Team_model extends Base_model {

	private $table = NULL;
	public function __construct() 
	{
		parent::__construct();
		$this->load->model('hero_model');
		$this->table=parent::init('hero_team');
	}
	
	public function select_all($params)
	{
		$sql = "select *  from `{$this->table}` order by `id`";
		$query = $this->db->query($sql);
		$hero_team = $query->result();
		$pagenum = $params['pagenum'];
		$pagesize = $params['pagesize'];
		$begin = ($pagenum-1)*$pagesize;
		$hero_team = array_slice($hero_team,$begin,$pagesize);
		return $hero_team;
	}
		public function is_update($hero_team_timestamp)
	{
		$sql = "SELECT COUNT(*) as count FROM `{$this->table}` WHERE UNIX_TIMESTAMP(`create_time`)>$hero_team_timestamp";
		$query = $this->db->query($sql);
		$hero_team_count = $query->row_array();
		return $hero_team_count['count'];
	}
	
	
}
