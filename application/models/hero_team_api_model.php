<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Hero_Team_Api_model  extends CI_Model {
        public function __construct()
		{
			parent::__construct();
			$this->load->model('hero_team_model');
        }
		
		public function select_all($params)
		{
			$result = $this->hero_team_model->select_all($params);
			$response['code']=200;
			$response['msg']='OK';
			$response['result'] = $result;
			return $response;
		}
		
}