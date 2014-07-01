<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Goods_Api_model  extends CI_Model {
        public function __construct()
		{
			parent::__construct();
			$this->load->model('hero_model');
			$this->load->model('goods_model');
			$this->load->model('goods_hero_model');
			$this->load->model('skill_model');
			$this->load->model('hero_skill_model');
        }
		
		public function select_goods($params)
		{
			$result = $this->goods_model->select_goods($params);
			$response['code']=200;
			$response['msg']='OK';
			$response['result']['down_offset'] = $params['pagenum'];
			$response['result']['content'] = $result;
			return $response;
		}
		
		public function get_goods($params)
		{
			$goods_id = $params['goods_id'];
			$result = $this->goods_model->get_goods($goods_id);
			$response['code']=200;
			$response['msg']='OK';
			$response['result']['down_offset'] = $params['pagenum'];
			$response['result']['content'] = $result;
			return $response;
		}
		
}