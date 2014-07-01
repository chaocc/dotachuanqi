<?php
defined('BASEPATH') OR exit('No direct script access allowed');
define('IN_ADMINCP', TRUE);

class Skill extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->Env_model->init_env();
		$this->load->model('User_model');
		$this->User_model->check_status();
		$this->load->model('Skill_model');
		$this->load->model('Hero_Skill_model');
		$this->load->helper('form');
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
		
			$list = $this->Skill_model->get_list($page,0);
			$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '技能',
			'admin_priv' => TRUE,
			'nav_active' => 'skill',
			'skill' => $list['items'],
			'pager' => mutli($list['total'], 'console/skill/index', PAGESIZE, 4),
		);
		$this->load->admin_tpl('skill', $_page_vars);
	}
	
	/**
	 *  已删除列表
	 *
	 */
	public function deleted()
	{
			$page = intval($this->uri->rsegment(3));
			$page = $page > 0 ? $page : 1;
		
			$list = $this->Skill_model->get_list($page,1);
			$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '技能',
			'admin_priv' => TRUE,
			'nav_active' => 'skill',
			'subnav_active' => 'deleted',
			'skill' => $list['items'],
			'pager' => mutli($list['total'], 'console/skill/index', PAGESIZE, 4),
		);
		$this->load->admin_tpl('skill', $_page_vars);
	}
	
	/**
	 * 删除技能 Ajax 操作
	 */
	public function remove()
	{
		$this->load->library('exjson');
		if ( ! $this->input->is_ajax_request()) {
			$url = array();
			$url[] = array('console/skill', '转到技能页');
			show_message("您访问的页面不存在", $url);
		}
		$id = $this->input->post('id', TRUE);
		if (!$id) {
			$this->exjson->set_ret('result', FALSE);
			$this->exjson->set_ret('errormsg', '参数有误');
			$this->exjson->output();
		}
		
		
		$ret = $this->Skill_model->remove($id);
		
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
	 * 恢复技能 Ajax 操作
	 */
	public function recover()
	{
		$this->load->library('exjson');
		if ( ! $this->input->is_ajax_request()) {
			$url = array();
			$url[] = array('console/skill', '转到技能页');
			show_message("您访问的页面不存在", $url);
		}
		$id = $this->input->post('id', TRUE);
		if (!$id) {
			$this->exjson->set_ret('result', FALSE);
			$this->exjson->set_ret('errormsg', '参数有误');
			$this->exjson->output();
		}
		
		$ret = $this->Skill_model->recover($id);
		
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
	 *    编辑技能信息
	 */
	public function edit()
	{
		header("Content-type:text/html;charset=utf-8");
		if (!$this->User_model->check_founder()){
			show_message("您无权访问该页面");
		}
		
		if ( $this->input->post('form_submit') ){
			if ( $this->_skill_form() ) {
				$url = array();
				$url[] = array('console/skill', '转到技能列表');
				show_message("技能更新成功", $url, MESSAGE_SUCCESS);
			} else {
				show_message("技能更新失败或者未发生变更");
			}
		}
		
		$skill_id = intval($this->uri->rsegment(3));
		
		if ( !$skill_id) {
			show_message("技能ID不正确");
		}

		
		$skill = $this->Skill_model->get_one($skill_id);
		$hero = $this->Hero_Skill_model->get_by_skill($skill_id);

		if (!$skill || !is_array($skill)) {
			show_message("技能不存在");
		}
		
		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '编辑技能',
			'admin_priv' => TRUE,
			'nav_active' => 'skill',
			'subnav_active' => 'edit',
			'form_action' => 'edit',
			'formhash' => formhash(),
			'skill' => $skill,
			'hero'=>$hero,
		);
		$this->load->admin_tpl('skill_form', $_page_vars);
	}
	
	private function _skill_form()
	{

		$formhash = trim(strip_tags($this->input->post('formhash')));
		if ( ! $formhash OR ($formhash != formhash()) ) {
			show_message("非法提交");
		}
		global $method;
		if($method=='edit')
		{
			$skill_id = intval($this->input->post('skill_id'));
		}
		
		$description = trim(strip_tags($this->input->post('description')));
		$skill_img = trim(strip_tags($this->input->post('skill_img')));
		$update_time=$create_time = date('Y-m-d H:i:s');
		$skill_name =  trim(strip_tags($this->input->post('skill_name')));
		$hero_id = intval($this->input->post('hero'));
		if ($method=='edit') {
				$params=array(
					'id'=>$skill_id,
					'skill_img'=>$skill_img,
					'skill_name'=>$skill_name,
					'description'=>$description,
					'update_time'=>$update_time
				);
		}else if ($method=='add'){
				$params=array(
					'skill_img'=>$skill_img,
					'skill_name'=>$skill_name,
					'description'=>$description,
					'create_time'=>$update_time
				);
		}
		$skill_id = $this->Skill_model->save($params);
		return $this->Hero_Skill_model->save_by_hero($hero_id,$skill_id);
	 }
	 
	 /**
	 *   添加新技能
	 */
	public function add()
	{
		if ( ! $this->User_model->check_founder() ){
			show_message("您无权访问该页面");
		}
		
		if ( $this->input->post('form_submit') ) {
			if ( $this->_skill_form() ) {
				$url = array();
				$url[] = array('console/skill', '转到技能列表');
				show_message("技能添加成功", $url, MESSAGE_SUCCESS);
			} else {
				show_message("技能添加失败");
			}
		}
		
		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '添加新技能',
			'admin_priv' => TRUE,
			'nav_active' => 'skill',
			'subnav_active' => 'add',
			'form_action' => 'add',
			'formhash' => formhash(),
		);
		$this->load->admin_tpl('skill_form', $_page_vars);
	}

}