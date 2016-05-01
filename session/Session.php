<?php

/**
 * session管理的工厂方法。
 * @author frogluo
 *
 */
class Session {
	
	private $ss;
	
	private function __construct($type = '') {
		if ($type == 'cookie' ||SESSION_TYPE == 'cookie') {
			include_once 'CookieSession.php';
			$this->ss = new CookieSession ();
		}else if ($type == 'db' ||SESSION_TYPE == 'db') {
			include_once 'DBSession.php';
			$this->ss = new DBSession ();
		} else if ($type == 'memcache' || SESSION_TYPE == 'memcache') {
			include_once 'MemcacheSession.php';
			$this->ss = new MemcacheSession ();
		} else {
			include_once 'FileSession.php';
			$this->ss = new FileSession ();
		}
	}
	
	/**
	 * 使用时，必须调用start方法开启session。
	 * @param string $sid
	 */
	function start($sid = '') {
		if (! session_id ()) {
			$this->ss->start ($sid);
		}
	}
	
	static function factory() {
		if (! isset ( $GLOBALS ['_session_ins'] ) || is_null ( $GLOBALS ['_session_ins'] )) {
			$GLOBALS ['_session_ins'] = new Session ();
		}
		return $GLOBALS ['_session_ins'];
	
	}
	
	function get($key, $default = '') {
		$res = $this->ss->get ( $key );
		if ($res) {
			return $res;
		} else {
			return $default;
		}
	}
	
	function set($key, $value) {
		return $this->ss->set ( $key, $value );
	}
	
	function delete($key) {
		return $this->ss->delete ( $key );
	}
	
	function destroy() {
		return $this->ss->destroy ();
	}

}
