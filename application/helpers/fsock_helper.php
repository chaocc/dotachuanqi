<?php
/**
 *      [wanmei.com] (C)2004-2013 Beijing Perfect World Network Technology Co., Ltd.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $ Id: fsock_helper.php UTF-8 2013-12-3 下午6:15:13Z Shalom $
 */
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('fsocketopen') ) {
	/**
	 * 网络流操作
	 * @param unknown $hostname
	 * @param number $port
	 * @param unknown $errno
	 * @param unknown $errstr
	 * @param number $timeout
	 * @return string
	 */
	function fsocketopen($hostname, $port = 80, &$errno, &$errstr, $timeout = 15) {
		$fp = '';
		if(function_exists('fsockopen')) {
			$fp = @fsockopen($hostname, $port, $errno, $errstr, $timeout);
		} elseif(function_exists('pfsockopen')) {
			$fp = @pfsockopen($hostname, $port, $errno, $errstr, $timeout);
		} elseif(function_exists('stream_socket_client')) {
			$fp = @stream_socket_client($hostname.':'.$port, $errno, $errstr, $timeout);
		}
		return $fp;
	}
}

if ( ! function_exists('dfsockopen') ) {
	/**
	 * 远程访问数据
	 * @param string $url
	 * @param number $limit
	 * @param string $post
	 * @param string $cookie
	 * @param string $bysocket
	 * @param string $ip
	 * @param number $timeout
	 * @param string $block
	 * @param string $encodetype
	 * @param string $allowcurl
	 * @param number $position
	 * @return string
	 */
	function dfsockopen($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 5, $block = TRUE, $encodetype  = 'URLENCODE', $allowcurl = TRUE, $position = 0) 
	{
		$return = '';
		$matches = parse_url($url);
		$scheme = $matches['scheme'];
		$host = $matches['host'];
		$path = (isset($matches['path']) &&$matches['path']) ? $matches['path'].((isset($matches['query']) && $matches['query']) ? '?'.$matches['query'] : '') : '/';
		$port = !empty($matches['port']) ? $matches['port'] : 80;
		
		if(function_exists('curl_init') && function_exists('curl_exec') && $allowcurl) {
			$ch = curl_init();
			$ip && curl_setopt($ch, CURLOPT_HTTPHEADER, array("Host: ".$host));
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			curl_setopt($ch, CURLOPT_URL, $scheme.'://'.($ip ? $ip : $host).':'.$port.$path);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
			if($post) {
				curl_setopt($ch, CURLOPT_POST, 1);
				if($encodetype == 'URLENCODE') {
					curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
				} else {
					parse_str($post, $postarray);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $postarray);
				}
			}
			if($cookie) {
				curl_setopt($ch, CURLOPT_COOKIE, $cookie);
			}
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			$data = curl_exec($ch);
			$status = curl_getinfo($ch);
			$errno = curl_errno($ch);
			curl_close($ch);
			if($errno || $status['http_code'] != 200) {
				return;
			} else {
				return !$limit ? $data : substr($data, 0, $limit);
			}
		}
		
		if($post) {
			$out = "POST $path HTTP/1.0\r\n";
			$header = "Accept: */*\r\n";
			$header .= "Accept-Language: zh-cn\r\n";
			$boundary = $encodetype == 'URLENCODE' ? '' : '; boundary='.trim(substr(trim($post), 2, strpos(trim($post), "\n") - 2));
			$header .= $encodetype == 'URLENCODE' ? "Content-Type: application/x-www-form-urlencoded\r\n" : "Content-Type: multipart/form-data$boundary\r\n";
			$header .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
			$header .= "Host: $host:$port\r\n";
			$header .= 'Content-Length: '.strlen($post)."\r\n";
			$header .= "Connection: Close\r\n";
			$header .= "Cache-Control: no-cache\r\n";
			$header .= "Cookie: $cookie\r\n\r\n";
			$out .= $header.$post;
		} else {
			$out = "GET $path HTTP/1.0\r\n";
			$header = "Accept: */*\r\n";
			$header .= "Accept-Language: zh-cn\r\n";
			$header .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
			$header .= "Host: $host:$port\r\n";
			$header .= "Connection: Close\r\n";
			$header .= "Cookie: $cookie\r\n\r\n";
			$out .= $header;
		}
		
		$fpflag = 0;
		if(!$fp = @fsocketopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout)) {
			$context = array(
				'http' => array(
					'method' => $post ? 'POST' : 'GET',
					'header' => $header,
					'content' => $post,
					'timeout' => $timeout,
				),
			);
			$context = stream_context_create($context);
			$fp = @fopen($scheme.'://'.($ip ? $ip : $host).':'.$port.$path, 'b', false, $context);
			$fpflag = 1;
		}
		
		if(!$fp) {
			return '';
		} else {
			stream_set_blocking($fp, $block);
			stream_set_timeout($fp, $timeout);
			@fwrite($fp, $out);
			/*
			$status = stream_get_meta_data($fp);
			if(!$status['timed_out']) {
				while (!feof($fp) && !$fpflag) {
					if(($header = @fgets($fp)) && ($header == "\r\n" ||  $header == "\n")) {
						break;
					}
				}
		
				if($position) {
					for($i=0; $i<$position; $i++) {
						$char = fgetc($fp);
						if($char == "\n" && $oldchar != "\r") {
							$i++;
						}
						$oldchar = $char;
					}
				}
		
				if($limit) {
					$return = stream_get_contents($fp, $limit);
				} else {
					$return = stream_get_contents($fp);
				}
			}
			*/
			if ( ! $fpflag ) {
				while ( ! feof($fp) ) {
					$header = @fgets($fp);
					$status = stream_get_meta_data($fp);
					
					if( $status['timed_out']) {
						@fclose($fp);
						return '';
					}
					
					if( $header == "\r\n" ||  $header == "\n" ) {
						break;
					}
				}
			}
			
			$oldchar = NULL;
			
			if($position) {
				for( $i = 0; $i < $position; $i++ ) {
					$char = @fgetc($fp);
					
					if( $status['timed_out']) {
						@fclose($fp);
						return '';
					}
					
					if($char == "\n" && $oldchar != "\r") {
						$i++;
					}
					$oldchar = $char;
				}
			}
			
			if($limit) {
				$return = stream_get_contents($fp, $limit);
			} else {
				$return = stream_get_contents($fp);
			}
			
			@fclose($fp);
			return $return;
		}
	}
}

/* End of file fsock_helper.php */
/* Location: ./application/helper/fsock_helper.php */