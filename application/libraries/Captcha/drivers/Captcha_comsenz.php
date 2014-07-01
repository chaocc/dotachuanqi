<?php
/**
 *      [wanmei.com] (C)2004-2013 Beijing Perfect World Network Technology Co., Ltd.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $ Id: Captcha_comsenz.php UTF-8 2013-11-27 下午2:27:20Z Shalom $
 */
defined('BASEPATH') OR exit('No direct script access allowed');

! defined('IN_CAPTCHA') && define('IN_CAPTCHA', TRUE);
define('COMSENZ_ROOT', dirname(__FILE__).DIRECTORY_SEPARATOR.'comsenz'.DIRECTORY_SEPARATOR);

class Captcha_comsenz extends CI_Driver {

	var $type 	= 0;	   // captcha type
	var $width 	= 100;	   // captcha image width, recommend: 100 - 200
	var $height = 30; 	   // captcha iamge height, recommend: 30 - 80
	var $background	= 1;   // 随机背景
	var $adulterate	= 0;   // 随机背景图形
	var $ttf 	= 0;	   // 随机ttf字体，位置 fonts
	var $angle 	= 1;	   // 给验证码文字增加随机的倾斜度，本设置只针对 TTF 字体的验证码
	var $warping = 1;	   // 给验证码文字增加随机的扭曲，本设置只针对 TTF 字体的验证码
	var $scatter = 0;	   // 图片打散，默认 0 不打散
	var $color 	= 1;	   // 给验证码的背景图形和文字增加随机的颜色
	var $size 	= 0;	   // 验证码文字的大小随机显示
	var $shadow = 1;	   // 给验证码文字增加阴影
	var $animator = 0;	   // 1 验证码将显示成 GIF 动画方式，0 验证码将显示成静态图片方式
	
	private $code = NULL;  // captcha code
	private $length = 4;   // 验证码的长度，此值暂时不允许修改  409 420 随机值最大和最小值会互换而导致的错误
	private $fontpath;
	private $fontcolor;
	private $im;
	private $gd_flag = NULL;
	
	/**
	 * 初始化
	 */
	public function __construct()
	{
		if ( ! defined('CHARSET') ) {
			$charset = strtoupper( config_item('charset') );
			define('CHARSET', $charset);
		}
		
		$this->fontpath = COMSENZ_ROOT.'font'.DIRECTORY_SEPARATOR;
	}
	
	/**
	 * 输出验证码图片
	 */
	public function display()
	{
		$this->_no_cache_header();
		
		if ( $this->gd_flag ) {
			$this->_image();
		} else {
			$this->_bitmap();
		}
		
	}
	
	/**
	 * 返回生成的验证码    顺序调整为优先获得验证码，之后执行生成操作
	 */
	public function get_code()
	{
		PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
		
		$this->gd_flag = $this->_check_gd_image();
		
		! $this->code && $this->_make_code();
		
		return $this->code;
	}
	
	/**
	 * 生成非bmp验证码
	 */
	private function _image() {
		$bgcontent = $this->_background();
		
		if($this->animator == 1 && function_exists('imagegif')) 
		{
			include_once COMSENZ_ROOT.'class_gifmerge.php';
			$trueframe = mt_rand(1, 9);
			
			$frame = array();
			
			for($i = 0; $i <= 9; $i++) 
			{
				$this->im = imagecreatefromstring($bgcontent);
				$x[$i] = $y[$i] = 0;
				$this->adulterate && $this->_adulterate();
				
				if($i == $trueframe) {
					$this->ttf && function_exists('imagettftext') || $this->type == 1 ? $this->_ttffont() : $this->_giffont();
					$d[$i] = mt_rand(250, 400);
					$this->scatter && $this->_scatter($this->im);
				} else {
					$this->_adulteratefont();
					$d[$i] = mt_rand(5, 15);
					$this->scatter && $this->_scatter($this->im, 1);
				}
				
				ob_start();
				imagegif($this->im);
				imagedestroy($this->im);
				$frame[$i] = ob_get_contents();
				ob_end_clean();
			}
			
			$anim = new GifMerge($frame, 255, 255, 255, 0, $d, $x, $y, 'C_MEMORY');
			header('Content-type: image/gif');
			echo $anim->getAnimation();
		} 
		else 
		{
			$this->im = imagecreatefromstring($bgcontent);
			$this->adulterate && $this->_adulterate();
			$this->ttf && function_exists('imagettftext') || $this->type == 1 ? $this->_ttffont() : $this->_giffont();
			$this->scatter && $this->_scatter($this->im);
			
			if(function_exists('imagepng')) {
				header('Content-type: image/png');
				imagepng($this->im, NULL, 9);
			} else {
				header('Content-type: image/jpeg');
				imagejpeg($this->im, NULL, 100);
			}
			
			imagedestroy($this->im);
		}
	}

	/**
	 * 背景生成
	 */
	private function _background() {
		$this->im = imagecreatetruecolor($this->width, $this->height);
		$backgrounds = $c = array();
	
		if( $this->background && function_exists('imagecreatefromjpeg') && function_exists('imagecolorat') && function_exists('imagecopymerge') && function_exists('imagesetpixel') && function_exists('imageSX') && function_exists('imageSY') ) 
		{
			if( $handle = @opendir(COMSENZ_ROOT.'background/' )) {
				while( $bgfile = @readdir($handle) ) {
					if(preg_match('/\.jpg$/i', $bgfile)) {
						$backgrounds[] = COMSENZ_ROOT.'background/'.$bgfile;
					}
				}
				@closedir($handle);
			}
			
			if($backgrounds) {
				$imwm = imagecreatefromjpeg($backgrounds[array_rand($backgrounds)]);
				$colorindex = imagecolorat($imwm, 0, 0);
				$c = imagecolorsforindex($imwm, $colorindex);
				$colorindex = imagecolorat($imwm, 1, 0);
				imagesetpixel($imwm, 0, 0, $colorindex);
				$c[0] = $c['red'];
				$c[1] = $c['green'];
				$c[2] = $c['blue'];
				imagecopymerge($this->im, $imwm, 0, 0, mt_rand(0, 200 - $this->width), mt_rand(0, 80 - $this->height), imageSX($imwm), imageSY($imwm), 100);
				imagedestroy($imwm);
			}
		}
	
		if( ! $this->background || ! $backgrounds ) 
		{
			for($i = 0;$i < 3;$i++) {
				$start[$i] = mt_rand(200, 255);$end[$i] = mt_rand(100, 150);$step[$i] = ($end[$i] - $start[$i]) / $this->width;$c[$i] = $start[$i];
			}
			
			for($i = 0;$i < $this->width;$i++) {
				$color = imagecolorallocate($this->im, $c[0], $c[1], $c[2]);
				imageline($this->im, $i, 0, $i, $this->height, $color);
				$c[0] += $step[0];$c[1] += $step[1];$c[2] += $step[2];
			}
			
			$c[0] -= 20;$c[1] -= 20;$c[2] -= 20;
		}
	
		ob_start();
		
		if(function_exists('imagepng')) {
			imagepng($this->im, NULL, 9);
		} else {
			imagejpeg($this->im, NULL, 100);
		}
		
		imagedestroy($this->im);
		$bgcontent = ob_get_contents();
		ob_end_clean();
		$this->fontcolor = $c;
		return $bgcontent;
	}

	/**
	 * 生成bmp验证码
	 */
	private function _bitmap() {
		$numbers = array
		(
			'B' => array('00','fc','66','66','66','7c','66','66','fc','00'),
			'C' => array('00','38','64','c0','c0','c0','c4','64','3c','00'),
			'E' => array('00','fe','62','62','68','78','6a','62','fe','00'),
			'F' => array('00','f8','60','60','68','78','6a','62','fe','00'),
			'G' => array('00','78','cc','cc','de','c0','c4','c4','7c','00'),
			'H' => array('00','e7','66','66','66','7e','66','66','e7','00'),
			'J' => array('00','f8','cc','cc','cc','0c','0c','0c','7f','00'),
			'K' => array('00','f3','66','66','7c','78','6c','66','f7','00'),
			'M' => array('00','f7','63','6b','6b','77','77','77','e3','00'),
			'P' => array('00','f8','60','60','7c','66','66','66','fc','00'),
			'Q' => array('00','78','cc','cc','cc','cc','cc','cc','78','00'),
			'R' => array('00','f3','66','6c','7c','66','66','66','fc','00'),
			'T' => array('00','78','30','30','30','30','b4','b4','fc','00'),
			'V' => array('00','1c','1c','36','36','36','63','63','f7','00'),
			'W' => array('00','36','36','36','77','7f','6b','63','f7','00'),
			'X' => array('00','f7','66','3c','18','18','3c','66','ef','00'),
			'Y' => array('00','7e','18','18','18','3c','24','66','ef','00'),
			'2' => array('fc','c0','60','30','18','0c','cc','cc','78','00'),
			'3' => array('78','8c','0c','0c','38','0c','0c','8c','78','00'),
			'4' => array('00','3e','0c','fe','4c','6c','2c','3c','1c','1c'),
			'6' => array('78','cc','cc','cc','ec','d8','c0','60','3c','00'),
			'7' => array('30','30','38','18','18','18','1c','8c','fc','00'),
			'8' => array('78','cc','cc','cc','78','cc','cc','cc','78','00'),
			'9' => array('f0','18','0c','6c','dc','cc','cc','cc','78','00')
		);
	
		foreach($numbers as $i => $number) {
			for($j = 0; $j < 6; $j++) {
				$a1 = substr('012', mt_rand(0, 2), 1).substr('012345', mt_rand(0, 5), 1);
				$a2 = substr('012345', mt_rand(0, 5), 1).substr('0123', mt_rand(0, 3), 1);
				mt_rand(0, 1) == 1 ? array_push($numbers[$i], $a1) : array_unshift($numbers[$i], $a1);
				mt_rand(0, 1) == 0 ? array_push($numbers[$i], $a1) : array_unshift($numbers[$i], $a2);
			}
		}
	
		$bitmap = array();
		for($i = 0; $i < 20; $i++) {
			for($j = 0; $j <= ($this->length - 1); $j++) {
				$bytes = $numbers[$this->code[$j]][$i];
				$a = mt_rand(0, 14);
				array_push($bitmap, $bytes);
			}
		}
	
		for($i = 0; $i < 8; $i++) {
			$a = substr('012345', mt_rand(0, 2), 1) . substr('012345', mt_rand(0, 5), 1);
			array_unshift($bitmap, $a);
			array_push($bitmap, $a);
		}
	
		$image = pack('H*', '424d9e000000000000003e000000280000002000000018000000010001000000'.
				'0000600000000000000000000000000000000000000000000000FFFFFF00'.implode('', $bitmap));
	
		header('Content-Type: image/bmp');
		echo $image;
	}
	
	/**
	 * 生成验证码
	 */
	private function _make_code()
	{
		$seccode_len = ceil($this->length / 3) * 3; // 此处确保随机的字符串长度足够截取
		$seccode = $this->_random($seccode_len, 1); // 此处的数字长度为 3 的倍数，按照中文字符的长度要求处理
		$seccodeunits = '';
		
		if( $this->type == 1 && $this->gd_flag ) {
			$characters = $this->_get_characters();
			$len = strtoupper(CHARSET) == 'GBK' ? 2 : 3; // 此值仅和读取验证码字符串时有关系
			
			//$code = array(substr($seccode, 0, 3), substr($seccode, 3, 3)); // 此处截取的长度固定为3
			$code = array();
			$code_len = 0;
			while (TRUE) {
				if ($code_len >= $seccode_len) {
					break;
				}
				$code[] = substr($seccode, $code_len, 3);
				$code_len += 3;
			}
			
			$seccode = '';
			$ch_length = count($code); // 中文长度确定为  $code 数组的长度
			for($i = 0; $i < $ch_length; $i++) {
				$seccode .= substr($characters, $code[$i] * $len, $len);
			}
		} else {
			$s = sprintf("%0{$this->length}s", base_convert($seccode, 10, 24));
			$seccodeunits = $this->_get_characters();
		}
		
		if($seccodeunits) {
			$seccode = '';
			for($i = 0; $i < $this->length; $i++) {
				$unit = ord($s{$i});
				$seccode .= ($unit >= 0x30 && $unit <= 0x39) ? $seccodeunits[$unit - 0x30] : $seccodeunits[$unit - 0x57];
			}
		}
		
		$this->code = $seccode;
	}

	/**
	 * 随机背景图形，不建议与背景图同时使用
	 */
	private function _adulterate() {
		$linenums = $this->height / 10;
		for($i = 0; $i <= $linenums;$i++) {
			$color = $this->color ? imagecolorallocate($this->im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)) : imagecolorallocate($this->im, $this->fontcolor[0], $this->fontcolor[1], $this->fontcolor[2]);
			$x = mt_rand(0, $this->width);
			$y = mt_rand(0, $this->height);
			
			if(mt_rand(0, 1)) {
				$w = mt_rand(0, $this->width);
				$h = mt_rand(0, $this->height);
				$s = mt_rand(0, 360);
				$e = mt_rand(0, 360);
				for($j = 0; $j < 3; $j++) {
					imagearc($this->im, $x + $j, $y, $w, $h, $s, $e, $color);
				}
			} else {
				$xe = mt_rand(0, $this->width);
				$ye = mt_rand(0, $this->height);
				imageline($this->im, $x, $y, $xe, $ye, $color);
				for($j = 0; $j < 3; $j++) {
					imageline($this->im, $x + $j, $y, $xe, $ye, $color);
				}
			}
		} // end for
	}
	
	/**
	 * 随机字体
	 */
	private function _adulteratefont() {
		$seccodeunits = 'BCEFGHJKMPQRTVWXY2346789';
		$x = $this->width / 4;
		$y = $this->height / 10;
		$text_color = imagecolorallocate($this->im, $this->fontcolor[0], $this->fontcolor[1], $this->fontcolor[2]);
		for($i = 0; $i <= 3; $i++) {
			$adulteratecode = $seccodeunits[mt_rand(0, 23)];
			imagechar($this->im, 5, $x * $i + mt_rand(0, $x - 10), mt_rand($y, $this->height - 10 - $y), $adulteratecode, $text_color);
		}
	}
	
	/**
	 * ttf 字体
	 */
	private function _ttffont() {
		$seccode = $this->code;
		$seccoderoot = $this->type == 1 ? $this->fontpath.'ch/' : $this->fontpath.'en/';

		$dirs = @opendir($seccoderoot);
		$seccodettf = array();
		while( $entry = @readdir($dirs)) {
			if($entry != '.' && $entry != '..' && in_array( strtolower( $this->_fileext($entry)), array('ttf', 'ttc') ) ) {
				$seccodettf[] = $entry;
			}
		}
	
		if(empty($seccodettf)) {
			$this->_giffont();
			return;
		}
	
		$seccodelength = $this->length;
		if($this->type == 1 && !empty($seccodettf)) {
			if(strtoupper(CHARSET) != 'UTF-8') {
				include_once COMSENZ_ROOT.'class_chinese.php';
				$cvt = new Chinese(CHARSET, 'utf8');
				$seccode = $cvt->Convert($seccode);
			}
			//$seccode = array(substr($seccode, 0, 3), substr($seccode, 3, 3));
			//$seccodelength = 2;
			$code = array();
			$seccode_len = ceil($this->length / 3) * 3;
			$code_len = 0;
			while (TRUE) {
				if ($code_len >= $seccode_len) {
					break;
				}
				$code[] = substr($seccode, $code_len, 3);
				$code_len += 3;
			}
			$seccode = $code;
			$seccodelength = count($code); // 中文正确的长度
		}
	
		$widthtotal = 0;
		$font_split_num = $this->type ? ($seccodelength * 3 + 1) : ($seccodelength + 2);
		$font_width_num = $this->type ? ($seccodelength * 3 + 2) : ($seccodelength * 2);
		for($i = 0; $i < $seccodelength; $i++) {
			$font[$i]['font'] = $seccoderoot.$seccodettf[array_rand($seccodettf)];
			$font[$i]['angle'] = $this->angle ? mt_rand(-30, 30) : 0;
			$font[$i]['size'] = $this->type ? $this->width / $font_split_num : $this->width / $font_split_num; // 7 6
			$this->size && $font[$i]['size'] = mt_rand($font[$i]['size'] - $this->width / 40, $font[$i]['size'] + $this->width / 20);
			$box = imagettfbbox($font[$i]['size'], 0, $font[$i]['font'], $seccode[$i]);
			$font[$i]['zheight'] = max($box[1], $box[3]) - min($box[5], $box[7]);
			$box = imagettfbbox($font[$i]['size'], $font[$i]['angle'], $font[$i]['font'], $seccode[$i]);
			$font[$i]['height'] = min( $this->height, max($box[1], $box[3]) - min($box[5], $box[7]) );
			$font[$i]['hd'] = $font[$i]['height'] - $font[$i]['zheight'];
			$font[$i]['width'] = (max($box[2], $box[4]) - min($box[0], $box[6])) + mt_rand(0, $this->width / $font_width_num); // 8
			$font[$i]['width'] = $font[$i]['width'] > $this->width / $seccodelength ? $this->width / $seccodelength : $font[$i]['width'];
			$widthtotal += $font[$i]['width'];
		}
	
		$x = mt_rand($font[0]['angle'] > 0 ? cos( deg2rad( 90 - $font[0]['angle'] ) ) * $font[0]['zheight'] : 1, $this->width - $widthtotal);
		!$this->color && $text_color = imagecolorallocate($this->im, $this->fontcolor[0], $this->fontcolor[1], $this->fontcolor[2]);
		
		for($i = 0; $i < $seccodelength; $i++) {
			if($this->color) {
				$this->fontcolor = array(mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
				$this->shadow && $text_shadowcolor = imagecolorallocate($this->im, 0, 0, 0);
				$text_color = imagecolorallocate($this->im, $this->fontcolor[0], $this->fontcolor[1], $this->fontcolor[2]);
			} elseif($this->shadow) {
				$text_shadowcolor = imagecolorallocate($this->im, 0, 0, 0);
			}
			$y = $font[0]['angle'] > 0 ? mt_rand($font[$i]['height'], $this->height) : mt_rand($font[$i]['height'] - $font[$i]['hd'], $this->height - $font[$i]['hd']);
			$this->shadow && imagettftext($this->im, $font[$i]['size'], $font[$i]['angle'], $x + 1, $y + 1, $text_shadowcolor, $font[$i]['font'], $seccode[$i]);
			imagettftext($this->im, $font[$i]['size'], $font[$i]['angle'], $x, $y, $text_color, $font[$i]['font'], $seccode[$i]);
			$x += $font[$i]['width'];
		}
	
		$this->warping && $this->_warping($this->im);
	}
	
	/**
	 * gif font
	 */
	private function _giffont() {
		$seccode = $this->code;
		$seccodedir = array();
		if(function_exists('imagecreatefromgif')) {
			$seccoderoot = COMSENZ_ROOT.'gif/';
			$dirs = @opendir($seccoderoot);
			while($dir = @readdir($dirs)) {
				if($dir != '.' && $dir != '..' && file_exists($seccoderoot.$dir.'/9.gif')) {
					$seccodedir[] = $dir;
				}
			}
		}
	
		$widthtotal = 0;
		$font = array();
		$for_cnt = $this->length - 1;
		for($i = 0; $i <= $for_cnt; $i++) {
			$this->imcodefile = $seccodedir ? $seccoderoot.$seccodedir[array_rand($seccodedir)].'/'.strtolower($seccode[$i]).'.gif' : '';
			
			if(!empty($this->imcodefile) && file_exists($this->imcodefile)) {
				$font[$i]['file'] = $this->imcodefile;
				$font[$i]['data'] = getimagesize($this->imcodefile);
				$font[$i]['width'] = $font[$i]['data'][0] + mt_rand(0, 6) - 4;
				$font[$i]['height'] = $font[$i]['data'][1] + mt_rand(0, 6) - 4;
				$font[$i]['width'] += mt_rand(0, max(0, $this->width / 5 - $font[$i]['width']));
				$widthtotal += $font[$i]['width'];
			} else {
				$font[$i]['file'] = '';
				$font[$i]['width'] = 8 + mt_rand(0, $this->width / 5 - 5);
				$widthtotal += $font[$i]['width'];
			}
		}
	
		$x = mt_rand(1, $this->width - $widthtotal);
		for($i = 0; $i <= $for_cnt; $i++) {
			$this->color && $this->fontcolor = array(mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
			if($font[$i]['file']) {
				$this->imcode = imagecreatefromgif($font[$i]['file']);
				if($this->size) {
					$font[$i]['width'] = mt_rand($font[$i]['width'] - $this->width / 20, $font[$i]['width'] + $this->width / 20);
					$font[$i]['height'] = mt_rand($font[$i]['height'] - $this->width / 20, $font[$i]['height'] + $this->width / 20);
				}
				$y = mt_rand(0, $this->height - $font[$i]['height']);
				if($this->shadow) {
					$this->imcodeshadow = $this->imcode;
					imagecolorset($this->imcodeshadow, 0, 0, 0, 0);
					imagecopyresized($this->im, $this->imcodeshadow, $x + 1, $y + 1, 0, 0, $font[$i]['width'], $font[$i]['height'], $font[$i]['data'][0], $font[$i]['data'][1]);
				}
				imagecolorset($this->imcode, 0 , $this->fontcolor[0], $this->fontcolor[1], $this->fontcolor[2]);
				imagecopyresized($this->im, $this->imcode, $x, $y, 0, 0, $font[$i]['width'], $font[$i]['height'], $font[$i]['data'][0], $font[$i]['data'][1]);
			} else {
				$y = mt_rand(0, $this->height - 20);
				if($this->shadow) {
					$text_shadowcolor = imagecolorallocate($this->im, 0, 0, 0);
					imagechar($this->im, 5, $x + 1, $y + 1, $seccode[$i], $text_shadowcolor);
				}
				$text_color = imagecolorallocate($this->im, $this->fontcolor[0], $this->fontcolor[1], $this->fontcolor[2]);
				imagechar($this->im, 5, $x, $y, $seccode[$i], $text_color);
			}
			$x += $font[$i]['width'];
		}
	
		$this->warping && $this->_warping($this->im);
	}
	
	/**
	 * 给验证码文字增加随机的扭曲
	 * @param unknown $obj
	 */
	private function _warping(&$obj) {
		$rgb = array();
		$direct = rand(0, 1);
		$width = imagesx($obj);
		$height = imagesy($obj);
		$level = $width / 20;
		for($j = 0;$j < $height;$j++) {
			for($i = 0;$i < $width;$i++) {
				$rgb[$i] = imagecolorat($obj, $i , $j);
			}
			for($i = 0;$i < $width;$i++) {
				$r = sin($j / $height * 2 * M_PI - M_PI * 0.5) * ($direct ? $level : -$level);
				imagesetpixel($obj, $i + $r , $j , $rgb[$i]);
			}
		}
	}
	
	/**
	 * 图片打散
	 * @param unknown $obj
	 * @param number $level
	 */
	private function _scatter(&$obj, $level = 0) {
		$rgb = array();
		$this->scatter = $level ? $level : $this->scatter;
		$width = imagesx($obj);
		$height = imagesy($obj);
		for($j = 0;$j < $height;$j++) {
			for($i = 0;$i < $width;$i++) {
				$rgb[$i] = imagecolorat($obj, $i , $j);
			}
			for($i = 0;$i < $width;$i++) {
				$r = rand(-$this->scatter, $this->scatter);
				imagesetpixel($obj, $i + $r , $j , $rgb[$i]);
			}
		}
	}

	/**
	 * 生成随机字符串
	 * @param interger $length
	 * @param bool $numeric
	 * @return string
	 */
	private function _random($length, $numeric = FALSE) {
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
	
	/**
	 * 获得用于生成验证码的字符串集合
	 * @return string
	 */
	private function _get_characters() {
		if ($this->type == 1) {
			return '的一是在了不和有大这主中人上为们地个用工时要动国产以我到他会作来分生对于学下级就年阶义发成部民可出能方进同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批如应形想制心样干都向变关点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九您取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培着河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑';
		}
		return 'BCEFGHJKMPQRTVWXY2346789';
	}
	
	/**
	 * 获得文件后缀名称
	 */
	public function _fileext($filename) {
		return addslashes(strtolower(substr(strrchr($filename, '.'), 1, 10)));
	}
	
	/**
	 * 检查是否支持 GD 图片制作
	 */
	private function _check_gd_image()
	{
		return ( function_exists('imagecreate') && function_exists('imagecolorset') && function_exists('imagecopyresized') &&
		function_exists('imagecolorallocate') && function_exists('imagechar') && function_exists('imagecolorsforindex') &&
		function_exists('imageline') && function_exists('imagecreatefromstring') && ( function_exists('imagegif') OR function_exists('imagepng') OR function_exists('imagejpeg') ) );
	}
	
	/**
	 * 设置输出不缓冲
	 */
	private function _no_cache_header() {
		@header("Expires: -1");
		@header("Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0", FALSE);
		@header("Pragma: no-cache");
	}
}

/* End of file Captcha_comsenz.php */
/* Location: ./application/libraries/Captcha/drivers/Captcha_comsenz.php */