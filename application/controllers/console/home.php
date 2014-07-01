<?php

defined('BASEPATH') OR exit('No direct script access allowed');
define('IN_ADMINCP', TRUE);

class Home extends CI_Controller {
	
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
		$_page_vars = array(
			'site_name' => SITENAME,
			'page_title' => '管理中心首页',
			'admin_priv' => TRUE,
		);
		$this->load->admin_tpl('home', $_page_vars);
	}
}
