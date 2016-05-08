<?php
//require_once PATH_LIB . 'encrypt.class.php';
/**
 * 通过实现SessionHandlerInterface接口。
 *
 * @author frogluo
 *        
 */
class CookieSessionHandler implements SessionHandlerInterface {
	private $isStart = false;
	private $S = array ();
	private $k = 'smesd';
	private $isChange = false;
	/**
	 * 初始化时注册方法，设置保存方式。
	 * 将session数据保存到数据库。
	 */
	function __construct() {
	}
	
	// 启动session
	function start($sid = '') {
		if ($sid) {
			session_id ( $sid );
		}
	}
	private function _start() {
		if (! $this->isStart) {
			session_set_cookie_params ( 0, "/", DOMAIN_NAME );
			session_set_save_handler ( $this );
			if (! ini_get ( "session.auto_start" )) {
				session_start ();
			}
			$this->isStart = true;
		}
	}
	
	/**
	 * session开始。
	 *
	 * @param string $save_path
	 *        	session保存路径(没有使用)
	 * @param string $session_name
	 *        	session名(没有使用)
	 * @return bool session正常时返回 true
	 */
	function open($save_path, $session_name) {
		return true;
	}
	
	/**
	 * session关闭。
	 *
	 * @return bool session正常关闭返回 true
	 */
	function close() {
		return true;
	}
	
	/**
	 * 读取session的数据。
	 *
	 * @param
	 *        	string session ID
	 * @return string session返回值
	 */
	function read($id) {
		$d = get_cookie ( $this->k );
		$this->S = $this->decode ( $d );
		return null;
	}
	
	/**
	 *
	 * @param
	 *        	string sessionID
	 * @param
	 *        	string session的值
	 * @return bool 成功返回 true
	 */
	function write($id, $session_data) {
		if ($this->isChange) {
			set_cookie ( $this->k, $this->encode ( $this->S ), 0 );
		}
		
		return true;
	}
	
	/**
	 * 消除session。
	 *
	 * @param
	 *        	string session
	 * @return bool session正常消除返回 true
	 */
	function destroy($id = '') {
		$this->_start ();
		del_cookie ( $this->k );
	}
	
	/**
	 * 回收。
	 */
	function gc($maxlifetime) {
		return true;
	}
	function get($key) {
		$this->_start ();
		if (isset ( $this->S [$key] )) {
			return $this->S [$key];
		} else {
			return null;
		}
	}
	function set($key, $value) {
		$this->_start ();
		$this->isChange = true;
		$this->S [$key] = $value;
		return true;
	}
	function delete($key) {
		$this->_start ();
		if (isset ( $this->S [$key] )) {
			$this->isChange = true;
			unset ( $this->S [$key] );
		}
		return true;
	}
	
	private function encode($s) {
		return json_encode ( $s );
		//return encrypt::encode ( json_encode ( $s ), session_id ().get_browser_user_agent().'!4j%k' );
	}
	private function decode($s) {
		return json_decode ( $s, true );
		//return json_decode ( encrypt::decode ( $s, session_id ().get_browser_user_agent().'!4j%k' ), true );
	}
}
