<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Hero_Strong_Api_model  extends CI_Model {
        public function __construct()
		{
			parent::__construct();
			$this->load->model('hero_model');
			$this->load->model('goods_model');
			$this->load->model('goods_hero_model');
			$this->load->model('skill_model');
			$this->load->model('hero_skill_model');
			$this->load->model('hero_strong_model');
        }
		
		public function select_all($params)
		{
			$result = $this->hero_strong_model->select_all($params);
			$response['code']=200;
			$response['msg']='OK';
			$response['result'] = $result;
			return $response;
		}
		

}