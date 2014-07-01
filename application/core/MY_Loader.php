<?php
/**
 *      [wanmei.com] (C)2004-2013 Beijing Perfect World Network Technology Co., Ltd.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $ Id: MY_Loader.php UTF-8 2013-11-5 下午4:16:36Z ZhangShaolong $
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Loader extends CI_Loader {
	
	public function __construct() {
		parent::__construct();
	}
	
	/**
	 * 后台模版调用
	 */
	public function admin_tpl ($view, $vars = array(), $use_header_footer = TRUE, $return = FALSE, $suffix = '.html', $tpl = 'admin') {
		$view = $tpl.'/'.$view.$suffix;
		if ( ! $return) {
			if ($use_header_footer) {
				$this->view("{$tpl}/header{$suffix}", $vars);
				$this->view($view);
				$this->view("{$tpl}/footer{$suffix}");
			} else {
				$this->view($view, $vars);
			}
		} else {
			$html = '';
			if ($use_header_footer) {
				$html .= $this->view("{$tpl}/header{$suffix}", $vars, TRUE);
				$html .= $this->view($view, array(), TRUE);
				$html .= $this->view("{$tpl}/footer{$suffix}", array(), TRUE);
			} else {
				$html .= $this->view($view, $vars, TRUE);
			}
			return $html;
		}
	}
	
	/**
	 * 前台模版
	 */
	public function template ($view, $vars = array(), $use_header_footer = TRUE, $return = FALSE, $suffix = '.html', $tpl = 'default') {
		return $this->admin_tpl($view, $vars, $use_header_footer, $return, $suffix, $tpl);
	}
	
}

/* End of file MY_Loader.php */
/* Location: ./application/core/MY_Loader.php */