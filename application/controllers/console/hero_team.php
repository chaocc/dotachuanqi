<?php

defined('BASEPATH') OR exit('No direct script access allowed');
define('IN_ADMINCP', TRUE);

class Hero_Team extends CI_Controller{
	public $type_options;
	public function __construct()
	{
		parent::__construct();
		$this->Env_model->init_env();
		$this->load->model('User_model');
		$this->load->model('Hero_Team_model');
		$this->User_model->check_status();
		 $this->load->helper(array('form','url'));
		$this->type_options=array(
			'推图阵容' =>'推图阵容',
			'远征阵容'=>'远征阵容',
		);
	}
	/**
	 * Index Page for this controller.
	 *
	 */
	public function index()
	{
			$page = intval($this->uri->rsegment(3));
			$page = $page > 0 ? $page : 1;
			$list = $this->Hero_Team_model->get_list($page,0);
			
			$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '阵容库',
			'admin_priv' => TRUE,
			'nav_active' => 'hero_team',
			'subnav_active' => FALSE,
			'hero_team' => $list['items'],
			'pager' => mutli($list['total'], 'console/hero_team/index', PAGESIZE, 4),
		);
		$this->load->admin_tpl('hero_team', $_page_vars);
	}
	
	public function add()
	{
		if ( ! $this->User_model->check_founder() ){
			show_message("您无权访问该页面");
		}
		
		if ( $this->input->post('form_submit') ) {
			if ( $this->_hero_team_form() ) {
				$url = array();
				$url[] = array('console/hero_team', '转到阵容库列表');
				show_message("阵容库添加成功", $url, MESSAGE_SUCCESS);
			} else {
				show_message("阵容库添加失败");
			}
		}
		
		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '添加新阵容',
			'admin_priv' => TRUE,
			'nav_active' => 'hero_team',
			'subnav_active' => 'add',
			'form_action' => 'add',
			'formhash' => formhash(),
		);
		$this->load->admin_tpl('hero_team_form', $_page_vars);
	
	}
	
	public function remove()
	{
		$this->load->library('exjson');
		if ( ! $this->input->is_ajax_request()) {
			$url = array();
			$url[] = array('console/hero_team', '转到阵容库页');
			show_message("您访问的页面不存在", $url);
		}
		$id = $this->input->post('id', TRUE);
		if (!$id) {
			$this->exjson->set_ret('result', FALSE);
			$this->exjson->set_ret('errormsg', '参数有误');
			$this->exjson->output();
		}
		
		
		$ret = $this->Hero_Team_model->remove($id);
		
		if ($ret) {
			$this->exjson->set_ret('result', TRUE);
			$this->exjson->set_ret('data', '操作成功');
		} else {
			$this->exjson->set_ret('result', FALSE);
			$this->exjson->set_ret('errormsg', '操作失败或未发生变更');
		}
		$this->exjson->output();
	}
	
	public function deleted()
	{
			$page = intval($this->uri->rsegment(3));
			$page = $page > 0 ? $page : 1;
		
			$list = $this->Hero_Team_model->get_list($page,1);
			$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '阵容库',
			'admin_priv' => TRUE,
			'nav_active' => 'hero_team',
			'subnav_active' => 'deleted',
			'hero_team' => $list['items'],
			'pager' => mutli($list['total'], 'console/hero_team/deleted', PAGESIZE, 4),
		);
		$this->load->admin_tpl('hero_team', $_page_vars);
	}
	
	/**
	 * 恢复 阵容库 Ajax 操作
	 */
	public function recover()
	{
		$this->load->library('exjson');
		if ( ! $this->input->is_ajax_request()) {
			$url = array();
			$url[] = array('console/hero_team', '转到阵容库页');
			show_message("您访问的页面不存在", $url);
		}
		$id = $this->input->post('id', TRUE);
		if (!$id) {
			$this->exjson->set_ret('result', FALSE);
			$this->exjson->set_ret('errormsg', '参数有误');
			$this->exjson->output();
		}
		
		$ret = $this->Hero_Team_model->recover($id);
		
		if ($ret) {
			$this->exjson->set_ret('result', TRUE);
			$this->exjson->set_ret('data', '操作成功');
		} else {
			$this->exjson->set_ret('result', FALSE);
			$this->exjson->set_ret('errormsg', '操作失败或未发生变更');
		}
		$this->exjson->output();
	}
	
			private function _hero_team_form()
	{

		$formhash = trim(strip_tags($this->input->post('formhash')));
		if ( ! $formhash OR ($formhash != formhash()) ) {
			show_message("非法提交");
		}

		global $method;
		if($method=='edit')
		{
			$id = intval($this->input->post('id'));
		}
		$name = trim(strip_tags($this->input->post('name')));
		$type = trim(strip_tags($this->input->post('type')));
		$reason = trim(strip_tags($this->input->post('reason')));
		$sort = trim(strip_tags($this->input->post('sort')));
		$update_time=$create_time = date('Y-m-d H:i:s');
		$hero_id = implode(',',$this->input->post('hero_id'));
		
		if ($method=='edit') {
				$params=array(
				'id'=>$id,
				'name'=>$name,
				'type'=>$type,
				'reason'=>$reason,
				'sort'=>$sort,
				'update_time'=>$update_time,
				'hero_id'=>$hero_id
			);
		}else if ($method=='add'){
			$params=array(
				'name'=>$name,
				'type'=>$type,
				'reason'=>$reason,
				'sort'=>$sort,
				'create_time'=>$create_time,
				'hero_id'=>$hero_id
			);
		}
		return $this->Hero_Team_model->save($params);
	 }
	 
	 /**
	 *    编辑阵容库信息
	 */
	public function edit()
	{
		if (!$this->User_model->check_founder()){
			show_message("您无权访问该页面");
		}
		
		if ( $this->input->post('form_submit') ) {
			if ( $this->_hero_team_form() ) {
				$url = array();
				$url[] = array('console/hero_team', '转到阵容库列表');
				show_message("阵容更新成功", $url, MESSAGE_SUCCESS);
			} else {
				show_message("阵容更新失败或者未发生变更");
			}
		}
		
		$hero_team_id = intval($this->uri->rsegment(3));
		
		if ( !$hero_team_id) {
			show_message("阵容ID不正确");
		}

		$hero_team = $this->Hero_Team_model->get_one($hero_team_id);
		if (!$hero_team || !is_array($hero_team)) {
			show_message("阵容不存在");
		}
		
		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '编辑英雄',
			'admin_priv' => TRUE,
			'nav_active' => 'hero_team',
			'subnav_active' => 'edit',
			'form_action' => 'edit',
			'formhash' => formhash(),
			'hero_team' => $hero_team,
		);
		$this->load->admin_tpl('hero_team_form', $_page_vars);
	}
	

}