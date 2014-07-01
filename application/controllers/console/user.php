<?php
/**
 *      [wanmei.com] (C)2004-2013 Beijing Perfect World Network Technology Co., Ltd.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $ Id: user.php UTF-8 2013-11-15 下午2:22:34Z Shalom $
 */
defined('BASEPATH') OR exit('No direct script access allowed');
define('IN_ADMINCP', TRUE);

class User extends CI_Controller {
	
	public function __construct()
	{
		parent::__construct();
	
		$this->Env_model->init_env();
		$this->load->model('User_model');
		$this->User_model->check_status();
	}

	/**
	 * Index Page for this controller.
	 *
	 */
	public function index()
	{
		$page = intval($this->uri->rsegment(3));
		$page = $page > 0 ? $page : 1;
		
		$list = $this->User_model->get_user_list($page);
		
		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '用户列表',
			'admin_priv' => TRUE,
			'nav_active' => 'user',
			'subnav_active' => FALSE,
			'users' => $list['items'],
			'pager' => mutli($list['total'], 'console/user/index', PAGESIZE, 4),
		);
		$this->load->admin_tpl('user_list', $_page_vars);
	}
	
	/**
	 * 添加新用户
	 */
	public function add()
	{
		if ( ! $this->User_model->check_founder() ){
			show_message("您无权访问该页面");
		}
		
		if ( $this->input->post('form_submit') ) {
			if ( $this->_user_form() ) {
				$url = array();
				$url[] = array('console/user', '转到用户列表');
				show_message("用户添加成功", $url, MESSAGE_SUCCESS);
			} else {
				show_message("用户添加失败");
			}
		}
		
		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '添加新用户',
			'admin_priv' => TRUE,
			'nav_active' => 'user',
			'subnav_active' => 'add',
			'form_action' => 'add',
			'formhash' => formhash(),
			'member' => array('user_id' => 0, 'founder' => 0, 'banned' => 0),
		);
		$this->load->admin_tpl('user_form', $_page_vars);
	}
	
	/**
	 * 编辑用户 （操作仅限 重置 密码）
	 */
	public function edit()
	{
		if ( ! $this->User_model->check_founder() ){
			show_message("您无权访问该页面");
		}
		
		if ( $this->input->post('form_submit') ) {
			if ( $this->_user_form() ) {
				$url = array();
				$url[] = array('console/user', '转到用户列表');
				show_message("用户更新成功", $url, MESSAGE_SUCCESS);
			} else {
				show_message("用户更新失败或者未发生变更");
			}
		}
		
		$user_id = intval($this->uri->rsegment(3));
		
		if ( ! $user_id || $user_id < 1) {
			show_message("用户Uid 不正确");
		}
		
		if ($user_id === 1) {
			show_message("该用户为保留的默认用户，不允许进行编辑");
		}
		
		$user = $this->User_model->get_user($user_id, USER_GET_BY_UID);
		
		if ( ! $user || !is_array($user)) {
			show_message("用户不存在");
		}
		
		$login_user = $this->User_model->get_login_user();
		
		if ($user['founder'] && $login_user['user_id'] != 1) {
			show_message("该用户具有最高权限，除默认最高管理员外，您无权管理");
		}
		
		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '编辑用户',
			'admin_priv' => TRUE,
			'nav_active' => 'user',
			'subnav_active' => 'edit',
			'form_action' => 'edit',
			'formhash' => formhash(),
			'member' => $user,
		);
		$this->load->admin_tpl('user_form', $_page_vars);
	}
	
	/**
	 * 处理用户提交保单
	 */
	private function _user_form()
	{
		$formhash = trim(strip_tags($this->input->post('formhash')));
		if ( ! $formhash OR ($formhash != formhash()) ) {
			show_message("非法提交");
		}
		
		global $method;
		$username = $email = '';
		$founder = 0;
		$banned = 0;
		
		if ($method == 'add') {
			$username = trim(strip_tags($this->input->post('username')));
			$email = trim(strip_tags($this->input->post('email')));
			
			//检查用户名和email
			$chk_username = $this->User_model->get_user($username, USER_GET_BY_NAME);
			if ($chk_username && is_array($chk_username)) {
				show_message("用户名已存在");
			}
			$chk_email = $this->User_model->get_user($email, USER_GET_BY_EMAIL);
			if ($chk_username && is_array($chk_username)) {
				show_message("E-Mail 已存在");
			}				
		}
		
		$password = trim(strip_tags($this->input->post('password')));
		
		if ($method != 'modpwd') {
			$founder = intval($this->input->post('founderEnable'));
			$founder = $founder ? 1 : 0;
			$banned = intval($this->input->post('bannedLimit'));
			$banned = $banned ? 1 : 0;
		}
		$user_id = intval($this->input->post('user_id'));
		
		if ( ($method == 'add' && ( ! $username OR ! $email OR ! isemail($email) OR !$password OR strlen($password) < 6) ) OR ($method == 'edit' && $password && strlen($password) < 6)  ) {
			$errmsg = $method == 'add' ? '用户名、Email不能为空或者Email不合法；或密码长度至少为6位' : '密码长度至少为6位';
			show_message($errmsg);
		}
		
		$params = array(
			'user_id' => $user_id,
			'user_name' => $username,
			'email' => $email,
			'password' => $password,
			'founder' => $founder,
			'banned' => $banned
		);
		
		if ($method == 'add') {
			$params['add_by'] = TRUE;
		} elseif ($method == 'edit') {
			unset($params['user_name']);
			unset($params['email']);
			$params['modify_by'] = TRUE;
		} else {
			unset($params['user_name']);
			unset($params['email']);
			unset($params['founder']);
			unset($params['banned']);
		}
		
		return $this->User_model->save_user($params);
		
		return FALSE;
	}
}

/* End of file user.php */
/* Location: ./application/controllers/console/user.php */