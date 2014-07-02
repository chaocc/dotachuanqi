<?php

defined('BASEPATH') OR exit('No direct script access allowed');
define('IN_ADMINCP', TRUE);

class Zq extends CI_Controller{
	public function __construct()
	{
		parent::__construct();
		$this->Env_model->init_env();
		$this->load->model('User_model');
		$this->load->model('Zq_model');
		$this->User_model->check_status();
		 $this->load->helper(array('form','url'));
	}

	/**
	 * Index Page for this controller.
	 *
	 */
	public function index()
	{
			$page = intval($this->uri->rsegment(3));
			$page = $page > 0 ? $page : 1;
		
			$list = $this->Zq_model->get_list($page,0);
			$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '最强阵容',
			'admin_priv' => TRUE,
			'nav_active' => 'zq',
			'zq' => $list['items'],
			'pager' => mutli($list['total'], 'console/zq/index', PAGESIZE, 4),
		);
		$this->load->admin_tpl('zq', $_page_vars);
	}
	
	/**
	 * 已删除最强阵容列表
	 *
	 */
	public function deleted()
	{
			$page = intval($this->uri->rsegment(3));
			$page = $page > 0 ? $page : 1;
		
			$list = $this->Zq_model->get_list($page,1);
			$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '最强阵容',
			'admin_priv' => TRUE,
			'nav_active' => 'zq',
			'subnav_active' => 'deleted',
			'zq' => $list['items'],
			'pager' => mutli($list['total'], 'console/zq/deleted', PAGESIZE, 4),
		);
		$this->load->admin_tpl('zq', $_page_vars);
	}
	
	/**
	 *   添加新最强阵容
	 */
	public function add()
	{
		if ( ! $this->User_model->check_founder() ){
			show_message("您无权访问该页面");
		}
		
		if ( $this->input->post('form_submit') ) 
		{
			if ( $this->_zq_form() ) {
				$url = array();
				$url[] = array('console/zq', '转到最强阵容列表');
				show_message("最强阵容添加成功", $url, MESSAGE_SUCCESS);
			} else {
				show_message("最强阵容添加失败");
			}
		}
		
		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '添加新最强阵容',
			'admin_priv' => TRUE,
			'nav_active' => 'zq',
			'subnav_active' => 'add',
			'form_action' => 'add',
			'formhash' => formhash(),
		);
		$this->load->admin_tpl('zq_form', $_page_vars);
	}
	
		public function edit()
	{
		if (!$this->User_model->check_founder()){
			show_message("您无权访问该页面");
		}
		
		if ( $this->input->post('form_submit') ) {
			if ( $this->_zq_form() ) {
				$url = array();
				$url[] = array('console/zq', '转到最强阵容列表');
				show_message("最强阵容更新成功", $url, MESSAGE_SUCCESS);
			} else {
				show_message("最强阵容更新失败或者未发生变更");
			}
		}
		
		$zq_id = intval($this->uri->rsegment(3));
		
		if ( !$zq_id) {
			show_message("最强阵容ID不正确");
		}

		
		$zq = $this->Zq_model->get_one($zq_id);
		if (!$zq || !is_array($zq)) {
			show_message("最强阵容不存在");
		}
		
		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '编辑最强阵容',
			'admin_priv' => TRUE,
			'nav_active' => 'zq',
			'subnav_active' => 'edit',
			'form_action' => 'edit',
			'formhash' => formhash(),
			'zq' => $zq,
		);
		$this->load->admin_tpl('zq_form', $_page_vars);
	}
	
			private function _zq_form()
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
		$hero_id = implode(',',$this->input->post('hero_id'));
		$name = trim(strip_tags($this->input->post('name')));
		$advantage = trim(strip_tags($this->input->post('advantage')));
		$disadvantage = trim(strip_tags($this->input->post('disadvantage')));
		$create_time = trim(strip_tags($this->input->post('create_time')));
		$url = trim(strip_tags($this->input->post('url')));
		
		if ($method=='edit') {
			$params=array(
			'id'=>$id,
			'name'=>$name,
			'advantage'=>$advantage,
			'disadvantage'=>$disadvantage,
			'create_time'=>$create_time,
			'url'=>$url,
			'hero_id'=>$hero_id
		);
		}else if ($method=='add'){
				$params=array(
				'name'=>$name,
				'advantage'=>$advantage,
				'disadvantage'=>$disadvantage,
				'create_time'=>$create_time,
				'url'=>$url,
				'hero_id'=>$hero_id,
			);
	 }
		return $this->Zq_model->save($params);
	 }

}