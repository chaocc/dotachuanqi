<?php

defined('BASEPATH') OR exit('No direct script access allowed');
define('IN_ADMINCP', TRUE);

class Hero_Strong extends CI_Controller{
	public function __construct()
	{
		parent::__construct();
		$this->Env_model->init_env();
		$this->load->model('User_model');
		$this->load->model('Hero_Strong_model');
		$this->load->model('Hero_model');
		$this->User_model->check_status();
	}
	public function add()
	{
		if ( ! $this->User_model->check_founder() ){
			show_message("您无权访问该页面");
		}
		
		if ( $this->input->post('form_submit') ) {
			if ( $this->_hero_stong_form() ) {
				$url = array();
				$url[] = array('console/hero_strong', '转到最强英雄列表');
				show_message("最强英雄添加成功", $url, MESSAGE_SUCCESS);
			} else {
				show_message("最强英雄添加失败");
			}
		}
		
		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '添加新最强英雄',
			'admin_priv' => TRUE,
			'nav_active' => 'hero_strong',
			'subnav_active' => 'add',
			'form_action' => 'add',
			'formhash' => formhash(),
		);
		$this->load->admin_tpl('hero_strong_form', $_page_vars);
	}
	private function _hero_stong_form()
	{
		$formhash = trim(strip_tags($this->input->post('formhash')));
		if ( ! $formhash OR ($formhash != formhash()) ) {
			show_message("非法提交");
		}
		global $method;
		
		$hero_name =trim(strip_tags($this->input->post('hero_name')));
		$hero_id = $this->Hero_model->get_hero_id($hero_name);
		$create_time = trim(strip_tags($this->input->post('create_time')));  // name_cn && card_name   必须有
		$name = trim(strip_tags($this->input->post('name')));  
		$advantage = trim(strip_tags($this->input->post('advantage')));
		$disadvantage = trim(strip_tags($this->input->post('disadvantage')));
		$hero_img = trim(strip_tags($this->input->post('hero_img')));
		$style = trim(strip_tags($this->input->post('style')));
		$create_time=$update_time=date('Y-m-d H:i:s');
		if (!$hero_id OR !$name OR !$advantage OR !$disadvantage) {
			show_message("英雄名或名称或英雄优势或英雄逆势不能为空","console/hero_strong");
		}
		if($method=='add')
		{
			$params=array(
				'hero_id'=>$hero_id,
				'name'=>$name,
				'advantage'=>$advantage,
				'disadvantage'=>$disadvantage,
				'hero_img'=>$hero_img,
				'style'=>$style,
				'create_time'=>$create_time,
			);
		}else{
			$params=array(
				'hero_id'=>$hero_id,
				'name'=>$name,
				'advantage'=>$advantage,
				'disadvantage'=>$disadvantage,
				'hero_img'=>$hero_img,
				'style'=>$style,
				'update_time'=>$update_time,
			);
		
		}
		return $this->Hero_Strong_model->save($params);
	 }
	 
	 	/**
	 * Index Page for this controller.
	 *
	 */
	public function index()
	{
			$page = intval($this->uri->rsegment(3));
			$page = $page > 0 ? $page : 1;
			$list = $this->Hero_Strong_model->get_list($page,0);
			
			$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '最强英雄',
			'admin_priv' => TRUE,
			'nav_active' => 'hero_strong',
			'subnav_active' => FALSE,
			'hero_strong' => $list['items'],
			'pager' => mutli($list['total'], 'console/hero_strong/index', PAGESIZE, 4),
		);
		$this->load->admin_tpl('hero_strong', $_page_vars);
	}
	
		/**
	 * 删除英雄 Ajax 操作
	 */
	public function remove()
	{
		$this->load->library('exjson');
		if ( ! $this->input->is_ajax_request()) {
			$url = array();
			$url[] = array('console/hero_strong', '转到最强英雄页');
			show_message("您访问的页面不存在", $url);
		}
		$id = $this->input->post('id', TRUE);
		if (!$id) {
			$this->exjson->set_ret('result', FALSE);
			$this->exjson->set_ret('errormsg', '参数有误');
			$this->exjson->output();
		}
		
		
		$ret = $this->Hero_Strong_model->remove($id);
		
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

			$list = $this->Hero_Strong_model->get_list($page,1);
			$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '最强英雄',
			'admin_priv' => TRUE,
			'nav_active' => 'hero_strong',
			'subnav_active'=>'deleted',
			'hero_strong' => $list['items'],
			'pager' => mutli($list['total'], 'console/hero_strong/index', PAGESIZE, 4),
		);
		$this->load->admin_tpl('hero_strong', $_page_vars);
	}
	
	/**
	 * 恢复最强英雄 Ajax 操作
	 */
	public function recover()
	{
		$this->load->library('exjson');
		if ( ! $this->input->is_ajax_request()) {
			$url = array();
			$url[] = array('console/hero_strong', '转到英雄页');
			show_message("您访问的页面不存在", $url);
		}
		$id = $this->input->post('id', TRUE);
		if (!$id) {
			$this->exjson->set_ret('result', FALSE);
			$this->exjson->set_ret('errormsg', '参数有误');
			$this->exjson->output();
		}
		
		$ret = $this->Hero_Strong_model->recover($id);
		
		if ($ret) {
			$this->exjson->set_ret('result', TRUE);
			$this->exjson->set_ret('data', '操作成功');
		} else {
			$this->exjson->set_ret('result', FALSE);
			$this->exjson->set_ret('errormsg', '操作失败或未发生变更');
		}
		$this->exjson->output();
	}
	
		/**
	 *    编辑英雄信息
	 */
	public function edit()
	{
		if (!$this->User_model->check_founder()){
			show_message("您无权访问该页面");
		}
		
		if ( $this->input->post('form_submit') ) {
		
			if ( $this->_hero_stong_form() ) {
				$url = array();
				$url[] = array('console/hero_strong', '转到最强英雄列表');
				show_message("最强英雄更新成功", $url, MESSAGE_SUCCESS);
			} else {
				show_message("最强英雄更新失败或者未发生变更");
			}
		}
		
		$hero_strong_id = intval($this->uri->rsegment(3));
		
		if ( !$hero_strong_id) {
			show_message("最强英雄ID不正确");
		}

		
		$hero_strong = $this->Hero_Strong_model->get_one($hero_strong_id);
		$hero = $this->Hero_model->get_hero($hero_strong['hero_id']);
		$hero_name = $hero['hero_name'];
		if (!$hero_strong || !is_array($hero_strong)) {
			show_message("最强英雄不存在");
		}
		
		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '编辑最强英雄',
			'admin_priv' => TRUE,
			'nav_active' => 'hero_strong',
			'subnav_active' => 'edit',
			'form_action' => 'edit',
			'formhash' => formhash(),
			'hero_strong' => $hero_strong,
			'hero_name'=>$hero_name,
		);
		$this->load->admin_tpl('hero_strong_form', $_page_vars);
	}
}