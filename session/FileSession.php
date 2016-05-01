<?php

/**
 * 
 * @author frogluo
 *
 */
class FileSession {
	
	function __construct() {
	}
	
	function start($sid = '') {
		//ini_set('session.cache_limiter', 'none');
		session_set_cookie_params ( 0, "/", DOMAIN_NAME );
		if ($sid) {
			session_id ( $sid );
		}
		
		if (! ini_get ( "session.auto_start" )) {
			session_start ();
		}
	}
	
	function get($key = '') {
		return $_SESSION [$key];
	}
	
	function set($key, $value) {
		$_SESSION [$key] = $value;
		return true;
	}
	
	function delete($key) {
		unset ( $_SESSION [$key] );
		return true;
	}
	
	function destroy() {
		session_destroy ();
	}
}

