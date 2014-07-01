<?php
/**
 *      [wanmei.com] (C)2004-2013 Beijing Perfect World Network Technology Co., Ltd.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $ Id: func_helper.php UTF-8 2013-11-6 上午10:53:06Z Shalom $
 */
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! defined('show_message')) {
	// 消息类型的常量定义
	define('MESSAGE_DEFAULT', 0);
	define('MESSAGE_PRIMARY', 1);
	define('MESSAGE_SUCCESS', 2);
	define('MESSAGE_INFO', 3);
	define('MESSAGE_WARING', 4);
	define('MESSAGE_DANGER', 5);	
	
	/**
	 * Show message page
	 * @param string $message
	 * @param array $jump_url style: array(array('href' => 'url', 'text' => 'description')) or array(array('url', 'text'))
	 * @param int $type  错误类型  0 默认样式 1 主要 2 成功 3 信息 4 警告 5 错误
	 * @param int $auto_back 返回上一页 0 不添加 1 添加为默认即第一个链接 2 添加到其它链接最后面  仅在设置了 $jump_url 时有效
	 * @param int $timeout
	 * @param bool $halt 是否终止
	 */
	function show_message($message, $jump_url = NULL, $type = MESSAGE_DANGER, $auto_back = 0, $timeout = 3, $halt = FALSE) {
		$message = trim(strip_tags($message));
		$msg_types = array(0=>'default', 1 =>'primary', 2 => 'success', 3 => 'info', 4=>'warning', 5=>'danger');
		$type = array_key_exists($type, $msg_types) ? $type : 3;
		$type = $msg_types[$type];
		$auto_back = intval($auto_back);
		$auto_back = in_array($auto_back, array(0, 1, 2)) ? $auto_back : 0;
		$timeout = intval($timeout);
		$timeout = $timeout > 0 ? $timeout : 3;
	
		$jump_urls = array();
	
		if ($jump_url && is_array($jump_url)) {
			foreach ($jump_url as $url) {
				if (!$url || !is_array($url) || count($url) != 2) {
					continue;
				}
				if (array_key_exists('href', $url) || array_key_exists('text', $url)) {
					$url = array($url['href'], $url['text']);
				}
				list($href, $text) = $url;
				$href = trim(strip_tags($href));
				$text = trim(strip_tags($text));
				if (!$href) {
					continue;
				}
				$url['href'] = $href;
				$url['text'] = $text;
				$jump_urls[] = $url;
			}
		}
	
		$CI =& get_instance();
		$msg_vars = array(
				'site_name' => '老虎游戏', //后续数据来源  数据库
				'page_title' => '信息提示',
				'message' => $message,
				'jump_url' => $jump_urls,
				'type' => $type,
				'timeout' => $timeout,
				'auto_back' => $auto_back,
				'halt' => $halt,
				'hide_stat' => TRUE, //隐藏统计
				'admin_priv' => FALSE, //跳转部分不跟踪管理状态标记
		);
		
		$tpl_func = 'admin_tpl'; //defined('IN_ADMINCP') ? 'admin_tpl' : 'template';
		echo $CI->load->$tpl_func('message', $msg_vars, TRUE, TRUE);
		exit();
	}
}

if ( ! function_exists('formhash')) {
	/**
	 * Forum Hash key
	 * @param string $specialadd
	 * @return string
	 */
	function formhash($specialadd = '') {
		$CI =& get_instance();
		$CI->load->model('User_model');
		$user = $CI->User_model->get_login_user();
		
		$hashadd = defined('IN_ADMINCP') ? 'Only For Laohu Game Activity Platform Admin Control Panel' : '';
		return substr(md5(substr(TIMESTAMP, 0, -7).$user['user_name'].$user['user_id'].'dpasfujsfvblskdgdosahfc;sadjf'.$hashadd.$specialadd), 8, 8);
	}
}

if ( ! function_exists('mutli')) {
	/**
	 * @see http://codeigniter.org.cn/user_guide/libraries/pagination.html
	 * @param unknown $total
	 * @param unknown $murl
	 * @param number $perpage
	 * @param string $uri_segment
	 * @param string $suffix  查询字符串 ?filter=。。。。。
	 * @return string
	 */
	function mutli ($total, $murl, $perpage = PAGESIZE, $uri_segment = NULL, $suffix = NULL) {
		$CI = get_instance();
		$CI->load->library('pagination');
	
		$murl = trim(strip_tags($murl));
		$total = intval($total);
		$total = $total < 0 ? 0 : $total;
		$perpage = intval($perpage);
		$perpage = $perpage > 0 ? $perpage : PAGESIZE;
		$config = array();
	
		$config['base_url'] = $murl;  //页面地址
		$config['total_rows'] = $total; // 记录总数
		$config['per_page'] = $perpage; //每页记录数
		//$config['cur_page'] = $curpage; // 当前页  不接受设置
	
		if ($uri_segment !== NULL) {
			$uri_segment = intval($uri_segment);
			if ($uri_segment > 0) {
				$config['uri_segment'] = $uri_segment;  //分页方法自动测定你 URI 的哪个部分包含页数
			}
		}
	
		$config['num_links'] = 5; // 放在你当前页码的前面和后面的“数字”链接的数量。
		$config['use_page_numbers'] = TRUE; // 默认分页URL中是显示每页记录数,启用use_page_numbers后显示的是当前页码
		$config['page_query_string'] = FALSE; // 你的链接将自动地被用查询字符串重写
	
		// 如果你希望在整个分页周围围绕一些标签
		$config['full_tag_open'] = '<ul class="pagination">'; // 把打开的标签放在所有结果的左侧。
		$config['full_tag_close'] = '</ul>'; // 把关闭的标签放在所有结果的右侧。
	
		// 自定义起始链接
		$config['first_link'] = '首页'; //你希望在分页的左边显示“第一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 。
		$config['first_tag_open'] = '<li>'; // “第一页”链接的打开标签。
		$config['first_tag_close'] = '</li>'; // “第一页”链接的关闭标签。
	
		// 自定义结束链接
		$config['last_link'] = '末页'; // 你希望在分页的右边显示“最后一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 。
		$config['last_tag_open'] = '<li>'; // “最后一页”链接的打开标签。
		$config['last_tag_close'] = '</li>'; // “最后一页”链接的关闭标签。
	
		// 自定义“下一页”链接
		$config['next_link'] = '&raquo;'; //你希望在分页中显示“下一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 。
		$config['next_tag_open'] = '<li>'; //“下一页”链接的打开标签。
		$config['next_tag_close'] = '</li>'; // “下一页”链接的关闭标签。
	
		// 自定义“上一页”链接
		$config['prev_link'] = '&laquo;'; //你希望在分页中显示“上一页”链接的名字。如果你不希望显示，可以把它的值设为 FALSE 。
		$config['prev_tag_open'] = '<li>'; // “上一页”链接的打开标签。
		$config['prev_tag_close'] = '</li>'; // “上一页”链接的关闭标签。
	
		// 自定义“当前页”链接
		$config['cur_tag_open'] = '<li class="active"><span>'; //	“当前页”链接的打开标签。
		$config['cur_tag_close'] = '<span class="sr-only">(current)</span></span></li>'; // “当前页”链接的关闭标签。
	
		// 自定义“数字”链接
		$config['num_tag_open'] = '<li>'; // “数字”链接的打开标签。
		$config['num_tag_close'] = '</li>'; //	“数字”链接的关闭标签。
	
		// 隐藏“数字”链接  如果你不想显示“数字”链接（比如只显示 “上一页” 和 “下一页”链接）你可以添加如下配置
		$config['display_pages'] = TRUE;
	
		// 给链接添加 CSS 类   如果你想要给每一个链接添加 CSS 类
		$config['anchor_class'] = "";
		
		// 设定url地址追加的查询字符串
		if ($suffix !== NULL) {
			$suffix = trim(strip_tags($suffix));
			if ($suffix && is_string($suffix)) {
				// 检查 ?, 如果存在问号，但不再第一位，则不追加 $suffix,否则自动追加 ? 并联合
				$q_pos = strpos($suffix, '?');
				if (FALSE !== $q_pos && $q_pos > 0) {
					;
				} else {
					$config['suffix'] = ( (FALSE !== $q_pos) ? '' : '?' ).$suffix;
				}
			}
		}
	
		$CI->pagination->initialize($config);
	
		return $CI->pagination->create_links();
	}
}

if ( ! function_exists('dmkdir')) {
	/**
	 * Create folders
	 * @param string $dir
	 * @param string $mode
	 * @param boolean $makeindex
	 * @return boolean
	 */
	function dmkdir($dir, $mode = 0755, $makeindex = TRUE){
		if( ! is_dir($dir)) {
			dmkdir(dirname($dir), $mode, $makeindex);// 确保上一级目录创建了
			@mkdir($dir, $mode);
			if( ! empty($makeindex)) {
				@touch($dir.'/index.html'); 
				@chmod($dir.'/index.html', 0555); // r 4 w 2 x 1
			}
		}
		return true;
	}
}

if ( ! function_exists('authcode')) {
	/**
	 * 字符串加密函数
	 * @param string $string
	 * @param string $operation
	 * @param string $key
	 * @param number $expiry
	 * @return string
	 */
	function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
		$ckey_length = 4;
		$key = md5($key != '' ? $key : AUTHKEY);
		$keya = md5(substr($key, 0, 16));
		$keyb = md5(substr($key, 16, 16));
		$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
	
		$cryptkey = $keya.md5($keya.$keyc);
		$key_length = strlen($cryptkey);
	
		$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
		$string_length = strlen($string);
	
		$result = '';
		$box = range(0, 255);
	
		$rndkey = array();
		for($i = 0; $i <= 255; $i++) {
			$rndkey[$i] = ord($cryptkey[$i % $key_length]);
		}
	
		for($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}
	
		for($a = $j = $i = 0; $i < $string_length; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}
	
		if($operation == 'DECODE') {
			if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
				return substr($result, 26);
			} else {
				return '';
			}
		} else {
			return $keyc.str_replace('=', '', base64_encode($result));
		}
	
	}
}

if ( ! function_exists('isemail')) {
	/**
	 * 判断是否为有效的邮箱地址
	 * @param unknown $email
	 * @return boolean
	 */
	function isemail($email) {
		return strlen($email) > 6 && strlen($email) <= 32 && preg_match("/^([A-Za-z0-9\-_.+]+)@([A-Za-z0-9\-]+[.][A-Za-z0-9\-.]+)$/", $email);
	}
}

if ( ! function_exists('random')) {
	/**
	 * 生成随机字符串
	 * @param string $length
	 * @param boolean $numeric
	 * @return string
	 */
	function random($length, $numeric = FALSE) {
		$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
		$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
		PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
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



/* End of file func_helper.php */
/* Location: ./application/helpers/func_helper.php */