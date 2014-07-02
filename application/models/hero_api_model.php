<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Hero_Api_model  extends CI_Model {
        public function __construct()
		{
			parent::__construct();
			$this->load->model('hero_model');
			$this->load->model('goods_model');
			$this->load->model('goods_hero_model');
			$this->load->model('skill_model');
			$this->load->model('hero_skill_model');
			$this->load->model('hero_strong_model');
			$this->load->model('hero_team_model');
        }
		

		// 英雄详情
		public function get_hero($params)
		{
			$response['msg'] = '查询英雄详情失败';
			if(isset($params['hero_id']))
			{
				$hero_id = $params['hero_id'];
				$result = $this->hero_model->get_hero($hero_id);
				$hero_skill = $this->hero_skill_model->get($hero_id);
				$result['skill'] = $hero_skill;
				$response['code']=200;
				$response['msg']='OK';
				$response['result']['down_offset'] = 1;
				$response['result']['content'] = $result;
			}
				return $response;
		}
		
		public function select_hero($params)
		{
			$response['msg'] = '查询英雄列表失败';
			if(isset($params))
			{
				$result = $this->hero_model->select_hero($params);
				$response['code'] = 200;
				$response['msg'] = 'OK';
				$response['result']['down_offset'] = $params['pagenum'];
				$response['result']['content'] = $result;
			}
				return $response;
		}
		
		public function is_update($params)
		{
			$hero_timestamp = $params['hero_timestamp'];
			$goods_timestamp = $params['goods_timestamp'];
			$hero_strong_timestamp = $params['hero_strong_timestamp'];
			$hero_team_timestamp = $params['hero_team_timestamp'];
			$hero_count= $this->hero_model->is_update($hero_timestamp);
			$goods_count=$this->goods_model->is_update($goods_timestamp);
			$hero_strong_count = $this->hero_strong_model->is_update($hero_strong_timestamp);
			$hero_team_count = $this->hero_team_model->is_update($hero_team_timestamp);
			$result['hero_count']=$hero_count;
			$result['goods_count']=$goods_count;
			$result['hero_strong_count']=$hero_strong_count;
			$result['hero_team_count']=$hero_team_count;
			$response['code']=200;
			$response['msg']='OK';
			$response['result'] = $result;
			return $response;
		}

}