<?php
/**
 *      [wanmei.com] (C)2004-2013 Beijing Perfect World Network Technology Co., Ltd.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $ Id: Captcha.php UTF-8 2013-11-27 上午10:08:39Z Shalom $
 */
defined('BASEPATH') OR exit('No direct script access allowed');

define('CAPTCHA_ROOT', APPPATH.'libraries/Captcha/');

class Captcha extends CI_Driver_Library {
	
	protected $valid_drivers 	= array(
		'captcha_simple', 'captcha_securimage', 'captcha_comsenz'
	);
	
	protected $_adapter	= 'simple';  // 选择可用的验证码适配器
	/** 验证码字符类型  0 字母和数字  1 中文   默认为 0    @used: comsenz, */
	var $type 	= 0;
	
	/** captcha image width, recommend: 100 - 200   @used: comsenz, simple, securimage */
	var $width 	= 100;
	
	/** captcha iamge height, recommend: 30 - 80    @used: comsenz, simple, securimage */
	var $height = 30;
	
	/** 随机背景     @used: comsenz, securimage */
	var $background	= 1;
	
	/** 随机背景图形     @used: comsenz */
	var $adulterate	= 0;
	
	/** 随机ttf字体，位置 fonts     @used: comsenz */
	var $ttf 	= 0;
	
	/** 给验证码文字增加随机的倾斜度，本设置只针对 TTF 字体的验证码     @used: comsenz */
	var $angle 	= 1;
	
	/** 给验证码文字增加随机的扭曲，本设置只针对 TTF 字体的验证码     @used: comsenz, securimage */
	var $warping = 1;
	
	/** 图片打散，默认 0 不打散     @used: comsenz */
	var $scatter = 0;
	
	/** 给验证码的背景图形和文字增加随机的颜色     @used: comsenz, simple, securimage */
	var $color 	= 1;
	
	/** 验证码文字的大小随机显示     @used: comsenz */
	var $size 	= 0;
	
	/** 给验证码文字增加阴影     @used: comsenz, simple */
	var $shadow = 1;
	
	/** 1 验证码将显示成 GIF 动画方式，0 验证码将显示成静态图片方式     @used: comsenz */
	var $animator = 0;
	
	/** 验证码长度     @used: simple, securimage @extra: 长度值为不短于该值 */
	var $length = 4;
	
	/** 验证码的有效期，单位 秒     */
	var $lifetime = 30;
	/** 是否使用数据库      */
	var $use_db = FALSE;
	/** 指定用户信息      */
	var $userinfo = NULL;
	
	private $use_referer = TRUE; // 测试用，是否验证 Referer
	private $code = NULL; // captcha code
	private $session_key = NULL;   // Session key
	private $CI = NULL; // CI object
	private $db_table = 'captha_session'; // 验证码数据库保存
	private $session_cookie_key = 'cpcsid'; //用于保存 session key 的 cookie key
	
	/**
	 * Constructor
	 *
	 * Initialize class properties.
	 *
	 * @return	void
	 */
	public function __construct()
	{
		if ( $this->use_referer ) {
			if ( ! empty($_SERVER['HTTP_REFERER']) ) {
				exit('Access Denied, No Referer');
			}
				
			$refererhost = @parse_url($_SERVER['HTTP_REFERER']);
			if ($refererhost === FALSE || ! isset($refererhost['host'])) {
				exit('Access Denied, Referer Error');
			}
			
			$refererhost['host'] .= (isset($refererhost['port']) && ! empty($refererhost['port'])) ? (':'.$refererhost['port']) : '';
			if($refererhost['host'] != $_SERVER['HTTP_HOST']) {
				exit('Access Denied');
			}
		}
		
		// note session start
		if ( ! session_id() ) {
			session_start();
		}
	}
	
	/**
	 * 显示验证码
	 * @return void
	 */
	public function display()
	{
		$this->_init_ci();
		
		$this->_init_database();
		
		$this->_generate_session_key();
		
		$this->_init_adapter_properties();
		
		$this->code = $this->_get_code();
		
		$this->_save_data();
		
		$this->{$this->_adapter}->display();
		//return $this->_get_code();
	}
	
	/**
	 * 效验验证码是否正确
	 * @param string $code
	 * @return bool
	 */
	public function verify_captcha($code)
	{
		$code = strtolower(trim(strip_tags($code)));
		if ( $code ) {
			$verify_code = $this->_get_data();
			if ($verify_code && $code === strtolower($verify_code)) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	/**
	 * 销毁验证码
	 * @return boolean
	 */
	public function destory_captcha()
	{
		$this->_delete_data();
		return TRUE;
	}
	
	/**
	 * Set adapter
	 * @param string $adapter
	 * @return void
	 */
	public function set_adapter($adapter)
	{
		$adapter = strtolower( trim( strip_tags($adapter) ) );
		if ( in_array('captcha_'.$adapter, $this->valid_drivers, TRUE) ) {
			$this->_adapter = $adapter;
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Return current adapter
	 * @return string
	 */
	public function get_adapter()
	{
		return $this->_adapter;
	}
	
	/**
	 * 初始化本类的CI
	 * 两个需求需要CI:  cookie 操作，如果存在 set_cookie 则不需要初始化  数据库操作，必须初始化CI
	 */
	private function _init_ci ()
	{
		if ($this->use_db === TRUE OR ! function_exists('set_cookie') ) {
			$this->CI =& get_instance();
			if ( ! function_exists('set_cookie') ) {
				$this->CI->load->helper('cookie');
			}
		}
	}
	
	/**
	 * 调用适配器接口获得验证码
	 * @return void
	 */
	private function _get_code()
	{
		return $this->{$this->_adapter}->get_code();
	}
	
	/**
	 * 保存验证码信息
	 */
	private function _save_data()
	{
		if ($this->use_db === TRUE) {
			$sql = "REPLACE INTO `{$this->db_table}` SET `sid` = ?, `captcha` = ?, `dateline` = ? ";
			$datas = array(addslashes($this->session_key), addslashes($this->code), time());
			$this->CI->db->query($sql, $datas);
			return TRUE;
		}
		
		$str_code = $this->_random(8)."_{$this->code}_".time();
		//set_cookie($this->session_key, $str_code, $this->lifetime);
		$_SESSION[$this->session_key] = $str_code;
		
		return TRUE;
	}
	
	/**
	 * 获取验证码数据
	 */
	private function _get_data()
	{
		if ($this->use_db === TRUE) {
			$sql = "SELECT * FROM `{$this->db_table}` WHERE `sid` = ?";
			$datas = array( addslashes($this->session_key) );
			$query = $this->CI->db->query($sql, $datas);
			if ( ! $query->num_rows() ) {
				return FALSE;
			}
			$row = $query->row_array();
			if (time() - $row['dataline'] < $this->lifetime) {
				return $row['captcha'];
			}
			return FALSE;
		}
		
		$str_code = $_SESSION[$this->session_key];
		if ($str_code) {
			list($random, $captcha, $time) = explode('_', $str_code);
			if ($captcha && ( time() - $time < $this->lifetime) ) {
				return $captcha;
			}
		}
		return FALSE;
	}
	
	/**
	 * 删除验证码数据
	 */
	private function _delete_data()
	{
		if ($this->use_db === TRUE) {
			$sql = "DELETE FROM `{$this->db_table}` WHERE `sid` = ?";
			$datas = array( addslashes($this->session_key) );
			$query = $this->CI->db->query($sql, $datas);
		} else {
			//set_cookie($this->session_key, '');
			unset($_SESSION[$this->session_key]);
		}
		set_cookie($this->session_cookie_key, '');
		return TRUE;
	}
	
	/**
	 * 初始化适配器属性参数
	 */
	private function _init_adapter_properties() {
		$this->{$this->_adapter}->type = $this->type;
		$this->{$this->_adapter}->width = $this->width;
		$this->{$this->_adapter}->height = $this->height;
		$this->{$this->_adapter}->background = $this->background;
		$this->{$this->_adapter}->adulterate = $this->adulterate;
		$this->{$this->_adapter}->ttf = $this->ttf;
		$this->{$this->_adapter}->angle = $this->angle;
		$this->{$this->_adapter}->warping = $this->warping;
		$this->{$this->_adapter}->scatter = $this->scatter;
		$this->{$this->_adapter}->color = $this->color;
		$this->{$this->_adapter}->size = $this->size;
		$this->{$this->_adapter}->shadow = $this->shadow;
		$this->{$this->_adapter}->animator = $this->animator;
		$this->{$this->_adapter}->length = $this->length;
		return TRUE;
	}
	
	/**
	 * 获得客户端IP地址
	 * @return string
	 */
	private function _get_client_ip() {
		$ip = $_SERVER['REMOTE_ADDR'];
		if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) 
		{
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) 
		{
			foreach ($matches[0] AS $xip) {
				if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) 
				{
					$ip = $xip;
					break;
				}
			}
		}
		return $ip;
	}
	
	/**
	 * 初始化数据库操作
	 */
	private function _init_database()
	{
		if ($this->use_db === TRUE) {
			if ( ! $this->CI->db ) {
				$this->use_db = FALSE;
				return FALSE;
			}
			if ( ! $this->CI->db->table_exists($this->db_table) ) {
				$db_table = $this->CI->db->dbprefix($this->db_table);
				$sql = "CREATE TABLE IF NOT EXISTS  `{$db_table}` (
							`sid` varchar(40) NOT NULL DEFAULT '',
							`captcha` varchar(15) NOT NULL DEFAULT '',
							`dateline` int(10) unsigned NOT NULL DEFAULT '0',
							PRIMARY KEY (`sid`),
							KEY `dateline` (`dateline`)
						) ENGINE=MEMORY DEFAULT CHARSET=utf8";
				$this->CI->db->query($sql);
			}
		}
	}
	
	/**
	 * 生成session key
	 * @param $force 强制重新生成
	 */
	private function _generate_session_key($force = FALSE) {
		$cookie_key = $this->session_cookie_key;
		$sid = get_cookie($cookie_key);
		if ( $sid && ! $force) {
			$this->session_key = $sid;
			return TRUE;
		}
		
		$str_suffix = '';
		if ($this->userinfo !== NULL) {
			if (is_string($this->userinfo)) {
				$str_suffix = "_{$this->userinfo}";
			} elseif (is_array($this->userinfo)) {
				foreach ( array_slice($this->userinfo, 0, 3, TRUE) as $field => $value ) {
					$str_suffix .= "_{$field}::{$value}";
				}
			}
		}
		$str_time = substr(time(), 0);
		$agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Invalid-http-user-agent';
		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Invalid-http-referer';
		$unique_str = uniqid($this->_random(6), TRUE);
		$this->session_key = md5(CAPTCHA_ROOT. "_{$agent}_" .$this->_get_client_ip(). "_{$referer}{$str_suffix}_{$unique_str}");
		
		set_cookie($cookie_key, $this->session_key);
		return TRUE;
	}
	
	/**
	 * 生成随机字符串
	 * @param unknown $length
	 * @param number $numeric
	 * @return string
	 */
	private function _random($length, $numeric = 0) {
		PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
		
		$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
		$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
		if($numeric) {
			$hash = '';
		} else {
			$hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
			$length--;
		}
		$max = strlen($seed) - 1;
		for($i = 0; $i < $length; $i++) {
			$hash .= $seed{mt_rand(0, $max)};
		}
		return $hash;
	}
}

/* End of file Captcha.php */
/* Location: ./application/libraries/Captcha/Captcha.php */