<?php
/**
 *      [wanmei.com] (C)2004-2013 Beijing Perfect World Network Technology Co., Ltd.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $ Id: hero.php UTF-8 2013-12-30 上午11:04:54Z Tang $
 *
 */
defined('BASEPATH') OR exit('No direct script access allowed');
define('IN_ADMINCP', TRUE);

class Hero extends CI_Controller {
	public $star_options;
	public $place_options;
	public $class_options;
	public $attributes_type_options;
	public function __construct()
	{
		parent::__construct();
		
		$this->Env_model->init_env();
		$this->load->model('User_model');
		$this->User_model->check_status();
		$this->load->model('Hero_model');
		$this->load->model('hero_strong_model');
		$this->load->helper('form');
		// $this->load->helper('string');
		 $this->load->helper(array('form','url'));
		$this->star_options= array( 
                  '1'  => '1星', 
                  '2'  => '2星', 
                  '3' =>  '3星', 
                  '4' => '4星', 
				  '5' => '5星'
                ); 
		
		$this->place_options = array(
				'前排' =>'前排',
				'中排' =>'中排',
				'后排' =>'后排',
		);
		
		$this->class_options = array(
			'力量' => '力量',
			'智力' => '智力',
			'敏捷' => '敏捷',
		);
		
		$this->attributes_type_options=array(
			'治疗' =>'治疗',
			'辅助'=>'辅助',
			'法师'=>'法师',
			'坦克'=>'坦克',
			'输出' =>'输出',
			'爆发' =>'爆发',
			'群体治疗'=>'群体治疗'
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
		
			$list = $this->Hero_model->get_list($page,0);
			$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '英雄',
			'admin_priv' => TRUE,
			'nav_active' => 'hero',
			'hero' => $list['items'],
			'pager' => mutli($list['total'], 'console/hero/index', PAGESIZE, 4),
		);
		$this->load->admin_tpl('hero', $_page_vars);
	}
	/**
	 * 已删除英雄列表
	 *
	 */
	public function deleted()
	{
			$page = intval($this->uri->rsegment(3));
			$page = $page > 0 ? $page : 1;
		
			$list = $this->Hero_model->get_list($page,1);
			$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '英雄',
			'admin_priv' => TRUE,
			'nav_active' => 'hero',
			'subnav_active' => 'deleted',
			'hero' => $list['items'],
			'pager' => mutli($list['total'], 'console/hero/deleted', PAGESIZE, 4),
		);
		$this->load->admin_tpl('hero', $_page_vars);
	}


	public function search(){
		$this->load->library('exjson');
		if ( ! $this->input->is_ajax_request()) {
			$url = array();
			$url[] = array('console/hero', '转到英雄页');
			show_message("您访问的页面不存在", $url);
		}
		
		$heroid = '';
		if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
			$heroid = $this->input->post('heroid', TRUE);
		} else {
			$this->exjson->set_ret('result', FALSE);
			$this->exjson->set_ret('errormsg', '请求方式有误');
			$this->exjson->output();
		}
		if (!$heroid) {
			$this->exjson->set_ret('result', FALSE);
			$this->exjson->set_ret('errormsg', '参数有误');
			$this->exjson->output();
		}
		$hero = $this->Hero_model->get_hero($heroid);
		$this->exjson->set_ret('result', TRUE);
		$this->exjson->set_ret('hero', $hero);
		$this->exjson->output();
	}
	
	
	/**
	 * 删除英雄 Ajax 操作
	 */
	public function remove()
	{
		$this->load->library('exjson');
		if ( ! $this->input->is_ajax_request()) {
			$url = array();
			$url[] = array('console/hero', '转到英雄页');
			show_message("您访问的页面不存在", $url);
		}
		$id = $this->input->post('id', TRUE);
		if (!$id) {
			$this->exjson->set_ret('result', FALSE);
			$this->exjson->set_ret('errormsg', '参数有误');
			$this->exjson->output();
		}
		
		
		$ret = $this->Hero_model->remove($id);
		
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
		
			if ( $this->_hero_form() ) {
				$url = array();
				$url[] = array('console/hero', '转到英雄列表');
				show_message("英雄更新成功", $url, MESSAGE_SUCCESS);
			} else {
				show_message("英雄更新失败或者未发生变更");
			}
		}
		
		$hero_id = intval($this->uri->rsegment(3));
		
		if ( !$hero_id) {
			show_message("英雄ID不正确");
		}

		
		$hero = $this->Hero_model->get_hero($hero_id);
		$equipment =explode(',',$hero['goods']);
		if (!$hero || !is_array($hero)) {
			show_message("英雄不存在");
		}
		
		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '编辑英雄',
			'admin_priv' => TRUE,
			'nav_active' => 'hero',
			'subnav_active' => 'edit',
			'form_action' => 'edit',
			'formhash' => formhash(),
			'hero' => $hero,
			'equipment'=>$equipment,
		);
		$this->load->admin_tpl('hero_form', $_page_vars);
	}
	
		private function _hero_form()
	{
		header("Content-type:text/html;charset=utf-8");
		$formhash = trim(strip_tags($this->input->post('formhash')));
		if ( ! $formhash OR ($formhash != formhash()) ) {
			show_message("非法提交");
		}

		global $method;
		if($method=='edit')
		{
			$hero_id = intval($this->input->post('hero_id'));
		}
		
		$hero_name= trim(strip_tags($this->input->post('hero_name')));
		$hero_img= trim(strip_tags($this->input->post('hero_img')));
		$description = trim(strip_tags($this->input->post('description')));
		$star = trim(strip_tags($this->input->post('star')));
		$place = trim(strip_tags($this->input->post('place')));
		$class = trim(strip_tags($this->input->post('class')));
		$tujin = trim(strip_tags($this->input->post('tujin')));
		$attributes_type = trim(strip_tags($this->input->post('attributes_type')));
		$power=intval($this->input->post('power'));
		$quick = intval($this->input->post('quick'));
		$intelligence = intval($this->input->post('intelligence'));
		$max = intval($this->input->post('max'));
		$attack = intval($this->input->post('attack'));
		$magic = intval($this->input->post('magic'));
		$armor = intval($this->input->post('armor'));
		$magic_defense = intval($this->input->post('magic_defense'));
		$crit = intval($this->input->post('crit'));
		$attributes="力量：".$power.",智力：".$intelligence.",敏捷：".$quick.",最大生命值：".$max.",物理攻击：".$attack.",魔法强度：".$magic.",物理护甲：".$armor.",魔法抗性：".$magic_defense.",物理暴击：".$crit;
		$create_time = $update_time=date('Y-m-d H:i:s');
		$goods_count = intval($this->input->post('goods_count'));
		$goods='';
		for($i=0;$i<=$goods_count-1;$i++)
		{
			if($i!=$goods_count-1)
			{
				if(count($this->input->post('goods_name_'.$i))>=1)
				{
					$goods=$goods.$this->input->post('goods_type_'.$i).':'.implode('|',$this->input->post('goods_name_'.$i)).',';
				}
			}else{
				if(count($this->input->post('goods_name_'.$i))>=1)
				{
					$goods=$goods.$this->input->post('goods_type_'.$i).':'.implode('|',$this->input->post('goods_name_'.$i));
				}
			}
		}
		if ($method=='edit') {
				$params=array(
					'id'=>$hero_id,
					'hero_img'=>$hero_img,
					'hero_name'=>$hero_name,
					'attributes'=>$attributes,
					'description'=>$description,
					'attributes_type'=>$attributes_type,
					'tujin'=>$tujin,
					'star'=>$star,
					'place'=>$place,
					'class'=>$class,
					'goods'=>$goods,
					'update_time'=>$update_time
				);
		}else if ($method=='add'){
					$params=array(
						'hero_img'=>$hero_img,
						'hero_name'=>$hero_name,
						'attributes'=>$attributes,
						'description'=>$description,
						'attributes_type'=>$attributes_type,
						'tujin'=>$tujin,
						'star'=>$star,
						'place'=>$place,
						'class'=>$class,
						'goods'=>$goods,
						'create_time'=>$create_time
				);
		}
		return $this->Hero_model->save_hero($params);
	 }
	 
	/**
	 *   添加新英雄
	 */
	public function add()
	{
		if ( ! $this->User_model->check_founder() ){
			show_message("您无权访问该页面");
		}
		
		if ( $this->input->post('form_submit') ) {
			if ( $this->_hero_form() ) {
				$url = array();
				$url[] = array('console/hero', '转到英雄列表');
				show_message("英雄添加成功", $url, MESSAGE_SUCCESS);
			} else {
				show_message("英雄添加失败");
			}
		}
		
		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '添加新英雄',
			'admin_priv' => TRUE,
			'nav_active' => 'hero',
			'subnav_active' => 'add',
			'form_action' => 'add',
			'formhash' => formhash(),
		);
		$this->load->admin_tpl('hero_form', $_page_vars);
	}
	
	/**
	 * 恢复英雄 Ajax 操作
	 */
	public function recover()
	{
		$this->load->library('exjson');
		if ( ! $this->input->is_ajax_request()) {
			$url = array();
			$url[] = array('console/hero', '转到英雄页');
			show_message("您访问的页面不存在", $url);
		}
		$id = $this->input->post('id', TRUE);
		if (!$id) {
			$this->exjson->set_ret('result', FALSE);
			$this->exjson->set_ret('errormsg', '参数有误');
			$this->exjson->output();
		}
		
		$ret = $this->Hero_model->recover($id);
		
		if ($ret) {
			$this->exjson->set_ret('result', TRUE);
			$this->exjson->set_ret('data', '操作成功');
		} else {
			$this->exjson->set_ret('result', FALSE);
			$this->exjson->set_ret('errormsg', '操作失败或未发生变更');
		}
		$this->exjson->output();
	}
	
		 public function get_hero()
	 {
		$q = strtolower($_GET["q"]);
		$hero_name= trim($q);
		$hero = $this->Hero_model->get($hero_name);
		echo json_encode($hero);
	 }

	
}