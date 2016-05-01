<?php

/**
 * 支持把session保存到memcache中。
 * 也可以参考DBSession.php方式，自己写回调方法控制session的读写。
 * 
 * @author frogluo
 *
 */
class MemcacheSession {
	
	function __construct() {
		$this->mem = new Memcache ();
		$host_arr = explode ( ',', MEMCACHE_HOST );
		$session_save_path = '';
		foreach ( $host_arr as $host ) {
			$this->mem->addServer ( $host, MEMCACHE_PORT );
			$session_save_path .= 'tcp://' . $host . ':' . MEMCACHE_PORT . '?persistent=1&weight=2&timeout=1&retry_interval=10,';
		}
		ini_set ( 'session.save_handler', 'memcache' );
		ini_set ( 'session.save_path', $session_save_path );
	}
	
	function start($sid = '') {
		//ini_set ( 'session.cache_limiter', 'none' );
		session_set_cookie_params ( 0, "/", DOMAIN_NAME );
		if ($sid) {
			session_id ( $sid );
		}
		
		if (! ini_get ( "session.auto_start" )) {
			session_start ();
		}
	}
	
	function get($key) {
		return $_SESSION [$key];
	}
	
	function set($key, $value) {
		return $_SESSION [$key] = $value;
	}
	
	function delete($key) {
		unset ( $_SESSION [$key] );
		return true;
	}
	
	function destroy() {
		session_destroy ();
	}
}

?>