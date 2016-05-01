<?php

/**
 * 将Session保存到数据库。<br>
 * 这里是通过session_set_save_handler注册回调方式实现，<br>
 * 保持PHP对session处理的一致性。<br>
 * 
 * @author frogluo
 *
 */
class DBSession {
	
	/**
	 * session数据是否有修改。
	 * 一般网关访问的时候，会话的数据只是读取，并未有改变，因此不需要回写。
	 * @var bool
	 */
	private $isChange = false;
	
	/**
	 * 初始化时注册方法，设置保存方式。
	 * 将session数据保存到数据库。
	 */
	function DBSession() {
		session_set_save_handler ( array (&$this, 'sessionOpen' ), array (&$this, 'sessionClose' ), array (&$this, 'sessionRead' ), array (&$this, 'sessionWrite' ), array (&$this, 'sessionDestroy' ), array (&$this, 'sessionGc' ) );
	}
	
	//启动session
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
	
	/**
	 * session开始。
	 *
	 * @param string $save_path session保存路径(没有使用)
	 * @param string $session_name session名(没有使用)
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
	 * 从数据库读取session的数据。
	 * 使用方法：$name = $_SESSION['myname'];
	 *
	 * @param string session ID
	 * @return string session返回值
	 */
	function sessionRead($id) {
		$objQuery = DBFactory::getInstance ();
		$arrRet = $objQuery->selectOne ( "session_data", "dt_session", "session_id = ?", array ($id ) );
		if (empty ( $arrRet )) {
			return '';
		} else {
			return $arrRet ['session_data'];
		}
	}
	
	/**
	 * 将session内容保存到数据库。
	 * 使用方式：$_SESSION['myname'] = $name
	 *
	 * @param string sessionID
	 * @param string session的值
	 * @return bool 成功返回 true
	 */
	function sessionWrite($id, $session_data) {
		if ($this->isChange) {
			$objQuery = DBFactory::getInstance ();
			$now = date ( 'Y-m-d H:i:s', time() );
			//使用ON DUPLICATE KEY UPDATE 方式 
			$objQuery->exec_prepare("insert into dt_session(session_id,session_data,create_date,update_date) value(?,?,?,?) on duplicate key update session_data = ?,update_date = ?", array($id,$session_data,$now,$now,$session_data,$now));
		}
		return true;
	}
	
	/**
	 * 消除session。
	 *
	 * @param string session 
	 * @return bool session正常消除返回 true
	 */
	function sessionDestroy($id) {
		$objQuery = DBFactory::getInstance ();
		$objQuery->delete ( "dt_session", "session_id = ?", array ($id ) );
		return true;
	}
	
	/**
	 * 当数据库保存的session数据过期，将其删除。
	 * 
	 * @param $maxlifetime 在数据库保存的有效期数（秒）
	 */
	function sessionGc($maxlifetime) {
		$objQuery = DBFactory::getInstance ();
		$now = time();
		$ttl = date('Y-m-d H:i:s', $now - 86400);
		$where = "update_date < '$ttl'";
		$objQuery->delete ( "dt_session", $where );
		return true;
	}
	
	function get($key) {
		if (isset ( $_SESSION [$key] )) {
			return $_SESSION [$key];
		} else {
			return null;
		}
	}
	
	function set($key, $value) {
		$this->isChange= true;
		$_SESSION [$key] = $value;
		return true;
	}
	
	function delete($key) {
		if (isset ( $_SESSION [$key] )) {
			$this->isChange= true;
			unset ( $_SESSION [$key] );
		}
		return true;
	}
	
	function destroy() {
		session_destroy ();
	}

}
