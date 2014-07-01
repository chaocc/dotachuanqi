<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Zq_model extends Base_model 
{

	private $table = NULL;
	public function __construct() 
	{
		$this->table=parent::init('zq');
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
}