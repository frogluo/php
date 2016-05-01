<?php
//require_once PATH_LIB . 'encrypt.class.php';

/**
 * 将session保存到cookie中。<br>
 * 这里是通过session_set_save_handler注册回调方式实现，<br>
 * 保持PHP对session处理的一致性。<br>
 * 
 * 因为PHP调用sessionWrite时是在页面缓冲关闭后才执行，这个时候不能再<br>
 * 往页面输出cookie了，所以在调用sset方法时就写入cookie。<br>
 * 
 * 读：先反序列化，再读<br>
 * 写：每次都写cookie,并更新本地变量<br>
 * 
 * 对session保存到cookie中的加密和序列化，需要自己实现。
 *
 * @author frogluo
 *        
 */
class CookieSession {
	private $isStart = false;
	private $S = array ();
	private $k = 'frogsd';

	function __construct() {
	}
	
	// 启动session
	function start($sid = '') {
		session_set_cookie_params ( 0, "/", DOMAIN_NAME );
	}
	function _start() {
		if (! $this->isStart) {
			session_set_save_handler ( array (
					&$this,
					'sessionOpen' 
			), array (
					&$this,
					'sessionClose' 
			), array (
					&$this,
					'sessionRead' 
			), array (
					&$this,
					'sessionWrite' 
			), array (
					&$this,
					'sessionDestroy' 
			), array (
					&$this,
					'sessionGc' 
			) );
			if (! ini_get ( "session.auto_start" )) {
				session_start ();
			}
			$d = $this->get_cookie ( $this->k );
			$this->S = $this->decode ( $d );
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
	function sessionOpen($save_path, $session_name) {
		return true;
	}
	
	/**
	 * session关闭。
	 *
	 * @return bool session正常关闭返回 true
	 */
	function sessionClose() {
		return true;
	}
	
	/**
	 * 读取session的数据。
	 *
	 * @param
	 *        	string session ID
	 * @return string session返回值
	 */
	function sessionRead($id) {
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
	function sessionWrite($id, $session_data) {
		return true;
	}
	
	/**
	 * 消除session。
	 *
	 * @param
	 *        	string session
	 * @return bool session正常消除返回 true
	 */
	function sessionDestroy($id) {
		return true;
	}
	
	/**
	 * 回收session。
	 */
	function sessionGc($maxlifetime) {
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
		$this->S [$key] = $value;
		$this->set_cookie ( $this->k, $this->encode ( $this->S ), 8640000 + time () );
		return true;
	}
	function delete($key) {
		$this->_start ();
		if(isset($this->S [$key])){
			unset ( $this->S [$key] );
			$this->set_cookie ( $this->k, $this->encode ( $this->S ), 8640000 + time () );
		}
		return true;
	}
	function destroy() {
		$this->del_cookie ( $this->k );
	}
	
	/**
	 * 请自己实现加密和序列化。
	 * 应该与session_id相关，session_id变了将不能解密和反序列化。
	 * @param string $s
	 * @return string
	 */
	private function encode($s) {
		return json_encode ( $s );
		//return encrypt::encode ( json_encode ( $s ), session_id ().get_browser_user_agent().'@dd%44(*' );
	}
	
	/**
	 * 请自己实现解密和反序列化。
	 * @param string $s
	 * @return mixed
	 */
	private function decode($s) {
		return json_decode ( $s, true );
		//return json_decode ( encrypt::decode ( $s, session_id ().get_browser_user_agent().'@dd%44(*'  ), true );
	}
	
	private function set_cookie($key, $val, $expire) {
		setcookie ( $key, $val, $expire, "/", DOMAIN_NAME );
	}
	
	private function get_cookie($key) {
		return isset ( $_COOKIE [$key] ) ? $_COOKIE [$key] : null;
	}
	
	private function del_cookie($key) {
		set_cookie ( $key, '', time() - 86400 );
	}
}
