<?php
/**
 *      [wanmei.com] (C)2004-2013 Beijing Perfect World Network Technology Co., Ltd.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $ Id: login.php UTF-8 2013-11-14 下午3:55:25Z Shalom $
 */
defined('BASEPATH') OR exit('No direct script access allowed');
define('IN_ADMINCP', TRUE);

class Login extends CI_Controller {
	
	public function __construct()
	{
		parent::__construct();
		
		$this->Env_model->init_env();
		$this->load->model('User_model');
	}

	/**
	 * Index Page for this controller.
	 *
	 */
	public function index()
	{
		if ( $this->User_model->check_login() ) {
			show_message("您已经登录");
		}
		
		if ( $this->input->post('form_submit') ) {
			$formhash = trim(strip_tags($this->input->post('formhash')));
			if ( ! $formhash OR ($formhash != formhash()) ) {
				show_message("非法提交");
			}
			
			$login_username = trim(strip_tags($this->input->post('username')));
			$login_password = trim(strip_tags($this->input->post('password')));
			
			if ( ! $login_username OR ! $login_password) {
				show_message("用户名或者密码为空");
			}
			
			$login_status = $this->User_model->login_user($login_username, $login_password, 1);
			
			if ( in_array($login_status, array( USER_NOT_EXIST, USER_BANNED, USER_PASSWORD_ERROR ), TRUE) ) {
				$err_msg = $login_status == USER_NOT_EXIST ? '用户不存在' : ($login_status == USER_BANNED ? '您的帐号已经被禁止登录' : '密码错误');
				show_message($err_msg);
			}
			
			$url = array();
			$url[] = array('console/home', '转到管理中心首页');
			show_message("登录成功", $url, MESSAGE_SUCCESS);
		}
		
		$_page_vars = array(
			'site_name' => SITENAME, 
			'page_title' => '用户登录',
			'formhash' => formhash(),
		);
		
		$this->load->admin_tpl('login', $_page_vars);
	}
	
	/**
	 * 修改自己的登录密码
	 */
	public function change()
	{
		
		if ( ! $this->User_model->check_login() ) {
			$url = array();
			$url[] = array('console/login', '转到管理员登录页面');
			show_message("您还没有登录", $url);
		}
		
		if ( $this->input->post('form_submit') ) {
			$formhash = trim(strip_tags($this->input->post('formhash')));
			if ( ! $formhash OR ($formhash != formhash()) ) {
				show_message("非法提交");
			}
				
			$login_oldpassword = trim(strip_tags($this->input->post('oldpassword')));
			$login_newpassword = trim(strip_tags($this->input->post('newpassword')));
			$login_renewpassword = trim(strip_tags($this->input->post('renewpassword')));
				
			if ( ! $login_oldpassword OR ! $login_newpassword OR ! $login_renewpassword) {
				show_message("请完善表单的各项内容");
			}
			
			if (strlen($login_newpassword) < 6 OR $login_renewpassword != $login_newpassword) {
				show_message("密码长度未达到6位以上或者两次密码不一致");
			}
			
			$user = $this->User_model->get_login_user();			
			// 验证旧密码
			if ( $this->User_model->gen_password($login_oldpassword, $user['salt']) != $user['password'] ) {
				show_message("旧密码不正确");
			}
			
			$params = array(
				'user_id' => $user['user_id'],
				'password' => $login_newpassword
			);
				
			$status = $this->User_model->save_user($params);

			if ( ! $status ) {
				show_message("密码修改失败或者未发生变更");
			}
			
			$this->User_model->logout_user();
			$url = array();
			$url[] = array('console/login', '转到登录页面');
			show_message("密码修改成功，请重新登录", $url, MESSAGE_SUCCESS);
			
		}

		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '密码修改',
			'formhash' => formhash(),
			'user' => $this->User_model->get_login_user(),
			'admin_priv' => TRUE,
		);
		
		$this->load->admin_tpl('changepwd', $_page_vars);
	}
	
	/**
	 * 退出登录
	 */
	public function logout()
	{
		$this->User_model->logout_user();
		$url = array();
		$url[] = array('./', '转到首页');
		show_message("退出登录成功", $url, MESSAGE_SUCCESS);
	}
}

/* End of file login.php */
/* Location: ./application/controllers/login.php */