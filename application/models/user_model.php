<?php
/**
 *      [wanmei.com] (C)2004-2013 Beijing Perfect World Network Technology Co., Ltd.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $ Id: User_model.php UTF-8 2013-11-14 上午10:40:04Z Shalom $
 */
defined('BASEPATH') OR exit('No direct script access allowed');
defined('IN_ADMINCP') OR exit('No direct script access allowed');

// 定义错误信息
define('USER_PARAMS_EMPTY', -1); //必填参数为空
define('USER_EMAIL_DUP', -2); // E-mail 已存在
define('USER_NAME_DUP', -3); // 用户名已存在
define('USER_NOT_EXIST', -4); // 用户不存在
define('USER_PASSWORD_ERROR', -5); //用户登录密码错误
define('USER_BANNED', -6); //用户被禁止
define('USER_NO_PRIVILEGE', -7); //无权限
define('USER_FORCE_CHANGE_PWD', -8); // 首次登录，强制修改密码
define('USER_FORCE_MODIFY_PWD', -9); // 管理员修改密码后首次登陆，强制修改密码

// 用户获取类型定义
define('USER_GET_BY_UID', 0);
define('USER_GET_BY_NAME', 1);
define('USER_GET_BY_EMAIL', 2);

class User_model extends CI_Model {
	
	static $user = NULL; //登录用户的信息
	private $table = NULL;
	private $cookie_key = 'authinfo';

	public function __construct() 
	{
		parent::__construct();
		
		$this->table = $this->db->dbprefix('users');
		$this->get_login();
	}
	
	/**
	 * 添加或者更新用户信息
	 * @param array $param  format : array('db_field_name' => 'new_value')
	 * @return mixed
	 */
	public function save_user($param) {
		if ( ! $param OR ! is_array($param) OR (isset($param['user_id']) && count($param) < 2) ) {
			return FALSE;
		}
		
		$user_id = intval($param['user_id']);
		$user_id = $user_id > 0 ? $user_id : 0;
		
		if ( isset($param['password']) && $param['password'] ) {
			$param['salt'] = isset($param['add_by']) ? '' : ( isset($param['modify_by']) ? random(2) : random(6) ); // salt 为空，首次登录强制修改密码  长度为 2 管理员修改密码后首次登录，执行强制修改密码
			$param['password'] = $this->gen_password($param['password'], $param['salt']);
		} else {
			unset($param['salt']);
			unset($param['password']);
		}
		
		$sql = ($user_id ? 'UPDATE' : 'INSERT INTO')." {$this->table} SET ";
		$db_values = array();

		foreach ($param as $db_field => $db_value) {
			$db_field = strtolower( trim( strip_tags( $db_field ) ) );
			if ( ! in_array($db_field, array('user_name', 'email', 'password', 'salt', 'founder', 'banned')) ) {
				continue;
			}

			$sql .= "`{$db_field}` = ?, ";
			$db_values[] = addslashes($db_value);
		}
		
		if ( ! $db_values ) {
			return FALSE;
		}
		
		$sql = trim( rtrim( trim($sql), ',' ) );
		
		if ($user_id) {
			$sql .= ' WHERE `user_id` = ?';
			$db_values[] = $user_id;
		} else {
			$sql .= ', `dateline`= ?, `manager`= ?';
			$db_values[] = TIMESTAMP;
			$db_values[] = self::$user['user_id']; //用登录用户的管理ID代替
		}

		$this->db->query($sql, $db_values);
		
		return $user_id ? $this->db->affected_rows() : $this->db->insert_id();
	}
	
	/**
	 * 用户登录
	 * @param string $user_name
	 * @param number $type  0 user_id 1 user_name 2 email
	 * @return mixed
	 */
	public function login_user($user_name, $password, $type = USER_GET_BY_NAME) {
		$password = trim(strip_tags($password));
		
		$user = $this->get_user($user_name, $type);
		
		if ( ! is_array($user)) {
			return $user;
		}
		
		if ($user['banned']) {
			return USER_BANNED;
		}
		
		if ($user['password'] != $this->gen_password($password, $user['salt'])) {
			return USER_PASSWORD_ERROR;
		}
		
		// 设置登录状态
		$user_str = "{$user['user_id']}\t{$user['password']}\t".TIMESTAMP;
		set_cookie($this->cookie_key, authcode($user_str, 'ENCODE'), 0);
		
		return TRUE;		
	}
	
	/**
	 * 用户退出登录
	 */
	public function logout_user()
	{
		delete_cookie($this->cookie_key);
		return TRUE;
	}
	
	/**
	 * 获得登录用户
	 */
	public function get_login()
	{
		$authinfo = trim(get_cookie($this->cookie_key));
		
		if ( ! $authinfo ) {
			return FALSE;
		}
		
		$authinfo = explode("\t", authcode($authinfo, 'DECODE'));
		
		if ( count($authinfo) != 3 ) {
			$this->logout_user();
			return FALSE;
		}
		
		list($user_id, $user_password, $dateline) = $authinfo;
		$user = $this->get_user($user_id, USER_GET_BY_UID);
		
		if ( ! is_array($user) OR $user['banned'] OR ($user_password != $user['password']) ) {
			$this->logout_user();
			return FALSE;
		}
		
		self::$user = $user;
		return TRUE;
	}
	
	/**
	 * 返回登录用户信息
	 */
	public function get_login_user()
	{
		return self::$user;
	}
	
	/**
	 * 检查是否处于登录窗台
	 */
	public function check_login() {
		return (self::$user != NULL);
	}
	
	/**
	 * 登录用户的权限检查
	 */
	public function check_privilege($isfounder = FALSE) 
	{
		if ( ! self::$user OR self::$user['banned']) {
			return FALSE;
		}
		
		if ($isfounder == TRUE && ! self::$user['founder']) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * 判定创始人的账户权限
	 */
	public function check_founder()
	{
		return $this->check_privilege(TRUE);
	}
	
	/**
	 * 判定是否需要强制修改密码
	 */
	public function check_force_change_pwd()
	{
		if ( self::$user ) {
			return ! self::$user['salt'] ? USER_FORCE_CHANGE_PWD : (strlen(self::$user['salt']) < 3 ? USER_FORCE_MODIFY_PWD : FALSE);
		}
		return FALSE;
	}
	
	/**
	 * 管理中心各界面需要用到的状态判定，自带 show_message 处理
	 */
	public function check_status()
	{
		if ( ! $this->check_login() ) {
			$url = array();
			$url[] = array('console/login', '转到登录页面');
			show_message("亲，请先登录哦", $url);
		}
		
		if ( ! $this->check_privilege()) {
			show_message('你无权访问该页面');
		}
		
		if ( ($pwd_status = $this->check_force_change_pwd()) ) {
			$err_msg = $pwd_status == USER_FORCE_CHANGE_PWD ? '这是您首次登录管理中心，请修改密码' : '这是您的密码被重置后的首次登录，请修改密码';
			$url = array();
			$url[] = array('console/login/change', '修改密码');
			show_message($err_msg, $url, MESSAGE_WARING);
		}
	}
	
	/**
	 * 获得用户信息
	 * @param string $user_name
	 * @param number $type 0 user_id 1 user_name 2 email
	 */
	public function get_user($user_name, $type = USER_GET_BY_NAME) {
		$type = intval($type);
		$type = in_array( $type, array(USER_GET_BY_UID, USER_GET_BY_NAME, USER_GET_BY_EMAIL), TRUE ) ? $type : USER_GET_BY_UID;
		$user_name = trim(strip_tags($user_name));
		
		if ( ! $user_name OR ($type == 0 && ! is_numeric($user_name) )) {
			return USER_NOT_EXIST;
		}
		
		$user_name = $type == USER_GET_BY_UID ? $user_name : addslashes($user_name);
		
		$db_field = $type == USER_GET_BY_NAME ? 'user_name' : ($type == USER_GET_BY_EMAIL ? 'email' : 'user_id');
		$query = $this->db->query("SELECT * FROM `{$this->table}` WHERE `{$db_field}` = ? ", array($user_name));
		if ( ! $query->num_rows() ) {
			return USER_NOT_EXIST;
		}
		
		$user = $query->row_array();
		$user['fmt_dateline'] = date(DATETIMEFROMAT, $user['dateline']);
		
		return $user;
	}
	
	/**
	 * 获得用户列表
	 */
	public function get_user_list($page = 1)
	{
		$page = intval($page);
		$page = $page > 0 ? $page : 1;
		
		$total = $this->get_user_total();
		$page_size = PAGESIZE;
		$max_page =  max(1, ceil($total / $page_size));
		$page = min(max(1, $page), $max_page);
		$start = ($page - 1) * $page_size;
		
		$list = array('total' => $total, 'curpage' => $page, 'maxpage' => $max_page, 'items' => array() );
		$where = 'WHERE 1';
		$order = 'ORDER BY `user_id` ASC';
		
		if ($total) {
			$query = $this->db->query("SELECT * FROM `{$this->table}` {$where} {$order} LIMIT ?, ? ", array($start, $page_size));
			foreach ($query->result_array() as $row) {
				$row['fmt_dateline'] = date("Y/m/d H:i:s", $row['dateline']);
		
				$list['items'][] = $row;
			}
		}
		
		return $list;
	}
	
	/**
	 * 获得用户表用户总数
	 */
	public function get_user_total()
	{
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `{$this->table}`");
		$row = $query->row_array();
		return $row ? $row['total'] : 0;
	}
	
	/**
	 * 生成密码加密串
	 * @param unknown $password
	 * @param unknown $salt
	 * @return string
	 */
	public function gen_password($password, $salt = '')
	{
		return md5( md5($password) . $salt );
	}
}

/* End of file User_model.php */
/* Location: ./application/models/User_model.php */