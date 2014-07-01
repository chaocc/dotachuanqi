<?php
/**
 *      [wanmei.com] (C)2004-2013 Beijing Perfect World Network Technology Co., Ltd.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $ Id: Env_model.php UTF-8 2013-11-6 上午10:06:39Z Shalom $
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Env_model extends CI_Model {
	
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 初始化环境
	 */
	public function init_env() 
	{
		if(PHP_VERSION < '5.3.0') {
			set_magic_quotes_runtime(0);
		}
		
		define('MAGIC_QUOTES_GPC', function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc());
		define('ICONV_ENABLE', function_exists('iconv'));
		define('MB_ENABLE', function_exists('mb_convert_encoding'));
		define('EXT_OBGZIP', function_exists('ob_gzhandler'));
		
		define('TIMESTAMP', time());
		
		$this->_timezone_set();
		
		define('SITE_URL', site_url());
		define('BASE_URL', base_url());
		define('CURRENT_URL', current_url());
		define('PAGESIZE', 10); //页码大小
		define('DATETIMEFROMAT', 'Y/m/d H:i:s'); // 常用的日期格式
		
		// authkey
		$authkey = trim(config_item('auth_key'));
		define('AUTHKEY', ($authkey ? $authkey : 'vxcfhwqpordhfadvs'));
		$site_name = trim(strip_tags(config_item('site_name')));
		define('SITENAME', ($site_name ? $site_name : '老虎游戏'));
		if ( ! defined('CHARSET') ) {
			$charset = strtoupper( config_item('charset') );
			define('CHARSET', $charset);
		}
		
		define('IMGDIR', 'http://img.laohu.com/ls/images/');
	}
	
	/**
	 * 设置时区
	 * @param number $timeoffset
	 */
	private function _timezone_set($timeoffset = 8) {
		if(function_exists('date_default_timezone_set')) {
			@date_default_timezone_set('Etc/GMT'.($timeoffset > 0 ? '-' : '+').(abs($timeoffset)));
		}
	}
	
}

/* End of file Env_model.php */
/* Location: ./application/models/Env_model.php */