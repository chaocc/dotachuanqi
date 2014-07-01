<?php

defined('BASEPATH') OR exit('No direct script access allowed');
define('IN_ADMINCP', TRUE);

class Goods extends CI_Controller {
	public $color_options;
	public function __construct()
	{
		parent::__construct();
		$this->Env_model->init_env();
		$this->load->model('User_model');
		$this->User_model->check_status();
		$this->load->model('Goods_model');
		$this->load->helper(array('form','url'));
		$this->color_options= array( 
		  '白色'  => '白色', 
		  '绿色'  => '绿色', 
		  '蓝色' =>  '蓝色', 
		  '紫色' => '紫色', 
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

			$list = $this->Goods_model->get_list($page,0);
			$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '物品',
			'admin_priv' => TRUE,
			'nav_active' => 'goods',
			'goods' => $list['items'],
			'pager' => mutli($list['total'], 'console/goods/index', PAGESIZE, 4),
		);
		$this->load->admin_tpl('goods', $_page_vars);
	}
	
	public function deleted()
	{
			$page = intval($this->uri->rsegment(3));
			$page = $page > 0 ? $page : 1;

			$list = $this->Goods_model->get_list($page,1);
			$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '物品',
			'admin_priv' => TRUE,
			'nav_active' => 'goods',
			'subnav_active'=>'deleted',
			'goods' => $list['items'],
			'pager' => mutli($list['total'], 'console/goods/index', PAGESIZE, 4),
		);
		$this->load->admin_tpl('goods', $_page_vars);
	}
	
	/**
	 * 删除物品 Ajax 操作
	 */
	public function remove()
	{
		$this->load->library('exjson');
		if ( ! $this->input->is_ajax_request()) {
			$url = array();
			$url[] = array('console/goods', '转到物品页');
			show_message("您访问的页面不存在", $url);
		}
		$id = $this->input->post('id', TRUE);
		if (!$id) {
			$this->exjson->set_ret('result', FALSE);
			$this->exjson->set_ret('errormsg', '参数有误');
			$this->exjson->output();
		}
		
		
		$ret = $this->Goods_model->remove($id);
		
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
	 * 恢复物品 Ajax 操作
	 */
	public function recover()
	{
		$this->load->library('exjson');
		if ( ! $this->input->is_ajax_request()) {
			$url = array();
			$url[] = array('console/goods', '转到物品页');
			show_message("您访问的页面不存在", $url);
		}
		$id = $this->input->post('id', TRUE);
		if (!$id) {
			$this->exjson->set_ret('result', FALSE);
			$this->exjson->set_ret('errormsg', '参数有误');
			$this->exjson->output();
		}
		
		$ret = $this->Goods_model->recover($id);
		
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
	 *    编辑物品信息
	 */
	public function edit()
	{
		
		if (!$this->User_model->check_founder()){
			show_message("您无权访问该页面");
		}
		
		if ( $this->input->post('form_submit') ) {
			if ( $this->_goods_form() ) {
				$url = array();
				$url[] = array('console/goods', '转到物品列表');
				show_message("物品更新成功", $url, MESSAGE_SUCCESS);
			} else {
				show_message("物品更新失败或者未发生变更");
			}
		}
		
		$goods_id = intval($this->uri->rsegment(3));
		
		if ( !$goods_id) {
			show_message("物品ID不正确");
		}

		
		$goods = $this->Goods_model->get_one($goods_id);
		
		if (!$goods || !is_array($goods)) {
			show_message("物品不存在");
		}
		
		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '编辑物品',
			'admin_priv' => TRUE,
			'nav_active' => 'goods',
			'subnav_active' => 'edit',
			'form_action' => 'edit',
			'formhash' => formhash(),
			'goods' => $goods,
		);
		$this->load->admin_tpl('goods_form', $_page_vars);
	}
	
	/**
	 *   添加新物品
	 */
	public function add()
	{
		if ( ! $this->User_model->check_founder() ){
			show_message("您无权访问该页面");
		}
		
		if ( $this->input->post('form_submit') ) {
			if ( $this->_goods_form() ) {
				$url = array();
				$url[] = array('console/goods', '转到物品列表');
				show_message("物品添加成功", $url, MESSAGE_SUCCESS);
			} else {
				show_message("物品添加失败");
			}
		}
		
		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '添加新物品',
			'admin_priv' => TRUE,
			'nav_active' => 'goods',
			'subnav_active' => 'add',
			'form_action' => 'add',
			'formhash' => formhash(),
		);
		$this->load->admin_tpl('goods_form', $_page_vars);
	}
	
		private function _goods_form()
		{
		$formhash = trim(strip_tags($this->input->post('formhash')));
		if ( ! $formhash OR ($formhash != formhash()) ) {
			show_message("非法提交");
		}
		global $method;
		if($method=='edit')
		{
			$goods_id = intval($this->input->post('goods_id'));
		}
		$goods_name= trim(strip_tags($this->input->post('goods_name')));
		$golds = trim(strip_tags($this->input->post('golds')));
		$color = trim(strip_tags($this->input->post('color')));
		$tujin = trim(strip_tags($this->input->post('tujin')));
		$description = trim(strip_tags($this->input->post('description')));
		$xiaoguo = trim(strip_tags($this->input->post('xiaoguo')));
		$update_time=$create_time=date('Y-m-d H:i:s');
		$goods_img =  trim(strip_tags($this->input->post('goods_img')));
		$tobe=implode(',',$this->input->post('tobe'));
		if ($method=='edit' ) {
			$params=array(
					'id'=>$goods_id,
					'goods_img'=>$goods_img,
					'golds'=>$golds,
					'color'=>$color,
					'tujin'=>$tujin,
					'description'=>$description,
					'xiaoguo'=>$xiaoguo,
					'update_time'=>$update_time,
					'tobe'=>$tobe
				);
		}else if ($method=='add'){
				$params=array(
					'goods_name'=>$goods_name,
					'goods_img'=>$goods_img,
					'golds'=>$golds,
					'color'=>$color,
					'tujin'=>$tujin,
					'description'=>$description,
					'xiaoguo'=>$xiaoguo,
					'create_time'=>$create_time,
					'tobe'=>$tobe
				);
		}

		return $this->Goods_model->save($params);
	 }
	 
	 public function get_goods()
	 {
		$q = strtolower($_GET["q"]);
		$good_name= trim($q);
		$goods = $this->Goods_model->get($good_name);
		echo json_encode($goods);
	 }

}