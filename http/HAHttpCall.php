<?php

/**
 * 支持高可用、负载均衡方式调用API。
 * 支持轮询、主从standby方式。
 * 只支持HTTP的REST方式。
 * 
 * 需要使用到xcache扩展，保存实时状态。
 * 在同一个http请求，并向后端多次调用时，都会转到同一个后端节点。
 *
 * @author frogluo
 *
 */
class HAHttpCall {
	
	/**
	 * 有多少个backend.
	 *
	 * @var int
	 */
	private $count = 0;
	/**
	 * 当前使用的backend。
	 */
	private $backend;
	
	/**
	 * 返回格式。STRING|JSON
	 * 默认是JSON。
	 *
	 * @var string
	 */
	public $format = 'JSON';
	
	/**
	 * xcache中保存的key前缀，如果运行多个HAHttpCall客户端，需要设置成不同的前缀。
	 *
	 * @var string
	 */
	public $xcache_prefix = 'HA_';
	
	/**
	 * 主节点。
	 */
	private $masters = array ();
	
	/**
	 * 备份节点。
	 */
	private $backups = array ();
	public function __construct() {
		if (! function_exists ( 'xcache_isset' )) {
			trigger_error ( "===>xcache not support<===", E_USER_ERROR );
			throw new Exception ( '===>xcache not support<===', 1001 );
		}
	}
	
	/**
	 * 增加一个后面节点。
	 * 要所Backend的backup值决定使用什么负载均衡方式。
	 * 例如，加多个backup=0的backend,那就使用轮询
	 * 如果有一个backup=1，那当其它节点挂了就请求取backup节点。
	 *
	 * @param unknown_type $url        	
	 */
	public function addHABackend(HABackend $be) {
		$this->count ++;
		$be->setId ( $this->xcache_prefix . $this->count );
		if ($be->backup == 1) {
			$this->backups [] = $be;
		} else {
			$this->masters [] = $be;
		}
	}
	
	/**
	 * get请求数据。
	 *
	 * @param string $action        	
	 * @param int $timeout
	 *        	连接超时间，默认30秒
	 */
	public function get($action, $timeout = 30) {
		return $this->callBackend ( $action, "GET", array (), $timeout );
	}
	
	/**
	 * post请求/提交数据。
	 *
	 * @param string $action        	
	 * @param array $data
	 *        	数组数据
	 * @param int $timeout
	 *        	连接超时间，默认30秒
	 */
	public function post($action, $data, $timeout = 30) {
		return $this->callBackend ( $action, "POST", $data, $timeout );
	}
	
	/**
	 * 获取一个可用的后端节点。
	 *
	 * 当下载的时候可能需要自己处理。
	 *
	 * @param string $action        	
	 *
	 * @return 当返回NULL时，表示没有节点可用。
	 */
	public function &getBackend() {
		if (empty ( $this->masters )) {
			throw new Exception ( 'no master backend', 1004 );
		}
		
		$a = array (); // 可用的节点
		foreach ( $this->masters as $key => $_bk ) {
			if (! $_bk->isDown ()) {
				$a [] = $key;
			}
		}
		// log_debug(json_encode($a));
		$c = count ( $a );
		if ($c > 0) {
			return $this->masters [$a [time () % $c]];
		}
		// log_debug($this->backups);die();
		if (! empty ( $this->backups )) {
			
			foreach ( $this->backups as $key => $_bk ) {
				if (! $_bk->isDown ()) {
					$a [] = $key;
				}
			}
			// log_debug($this->backups);die();
			$c = count ( $a );
			if ($c > 0) {
				return $this->backups [$a [time () % $c]];
			}
		}
		return $this->masters [0];
	}
	
	/**
	 * 向后端请求。
	 *
	 * @param HABackend $be        	
	 * @param string $method        	
	 * @param array $data        	
	 * @param int $timeout        	
	 */
	public function callBackend($action, $method = 'GET', $data = array(), $timeout = 30) {
		if (! $this->backend) {
			// 在同一个实例中，多次调用使用同一个backend
			$this->backend = &$this->getBackend ();
			if ($this->backend == NULL) {
				return null;
			}
		}
		for($i = 0; $i < $this->count; $i ++) {
			// log_debug(json_encode($this->backend));
			try {
				return $this->callApi ( $this->backend, $action, $method, $data, $timeout );
			} catch ( Exception $e ) {
				$this->backend->setFail ();
			}
			
			$this->backend = &$this->getBackend ();
			if ($this->backend == NULL) {
				return null;
			}else if($this->backend->down == 1){
				return null;
			}
		}
		
		return null;
	}
	function &callApi(&$be, $action, $method = 'GET', $data = array(), $timeout = 30) {
		$curl = curl_init ();
		$url = $be->base_url . $action;
		curl_setopt ( $curl, CURLOPT_TIMEOUT, $timeout );
		// curl_setopt ( $curl, CURLOPT_ACCEPTTIMEOUT_MS, 300 );
		// curl_setopt ( $curl, CURLOPT_NOSIGNAL, 1 );
		switch ($method) {
			case "POST" :
				curl_setopt ( $curl, CURLOPT_POST, 1 );
				if ($data)
					// curl_setopt ( $curl, CURLOPT_POSTFIELDS, $data );
					if (class_exists ( '\CURLFile' )) {
						curl_setopt ( $curl, CURLOPT_SAFE_UPLOAD, true );
					} else {
						if (defined ( 'CURLOPT_SAFE_UPLOAD' )) {
							curl_setopt ( $curl, CURLOPT_SAFE_UPLOAD, false );
						}
					}
				curl_setopt ( $curl, CURLOPT_POSTFIELDS, $data );
				break;
			case "PUT" :
				curl_setopt ( $curl, CURLOPT_PUT, 1 );
				curl_setopt ( $curl, CURLOPT_CUSTOMREQUEST, 'PUT' );
				break;
			default :
				if (! empty ( $data )) {
					$url .= '?' . http_build_query ( $data );
				}
		}
		curl_setopt ( $curl, CURLOPT_URL, $url );
		curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
		if (substr ( 'https', 0, 5 ) == 'https') {
			curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, false );
		}
		
		$err = curl_error ( $curl );
		if ($err != '') {
			throw new Exception ( 'request backend fail', 1002 );
		}
		
		$ret = curl_exec ( $curl );
		if ($this->format == 'JSON') {
			$ret = json_decode ( $ret, true );
		}
		if (! $this->checkFail ( $ret )) {
			throw new Exception ( 'request backend fail', 1002 );
		}
		$be->setOk ();
		curl_close ( $curl );
		return $ret;
	}
	
	/**
	 * 对结果进行判断。
	 *
	 * @param mix $curl_ret
	 *        	根据format转化的类型。
	 * @return boolean
	 */
	public function checkFail(&$curl_ret) {
		if (! $curl_ret) {
			return false;
		}
		return true;
	}
}

/**
 * 所要调用的服务的对象。
 *
 * @author lihui_luo
 *        
 */
class HABackend {
	public $id;
	
	/**
	 * 是否是备份节点，standby方式要指定。0|1
	 * 当非备份节点失效时，将访问$backup节点，
	 * 类似于nginx反向代理时的backup参数。
	 */
	public $backup = 0;
	
	
	public $down = 0;
	
	/**
	 * 前缀地址，包括端口号，如
	 * http://192.168.0.1:8868/page
	 * http://192.168.0.1/page
	 * @var string
	 */
	public $base_url;
	
	/**
	 * 请求超时时间。
	 * 如果超过这个时间没有反应，为失败.
	 *
	 * @var int 毫秒
	 */
	public $connect_time_out = 100;
	
	/**
	 * 如果失败次数为fail_time，设置为down.
	 *
	 *
	 * @var int
	 */
	public $fail_time = 5;
	
	/**
	 * 失败后多长时间重试
	 *
	 * @var int 秒
	 */
	public $try_interval = 5;
	
	/**
	 * 标识backend
	 *
	 * @param int $id        	
	 */
	public function setId($id) {
		$this->id = $id;
	}
	public function isDown() {
		$ft = xcache_get ( $this->id . 'ft' );
		$lt = xcache_get ( $this->id . 'lt' );
		
		if (! $ft) {
			$ft = 0;
		}
		if (! $lt) {
			$lt = 0;
		}
		
		if ($this->down == 1) {
			return true;
		}
		
		if (time () - $lt > $this->try_interval) {
			log_debug(time () - $lt);
			//xcache_set ( $this->id . 'lt', time () );
			return false;
		}
		if ($ft > $this->fail_time) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 调用失败。
	 */
	public function setFail() {
		xcache_inc ( $this->id . 'ft', 1 );
		xcache_set ( $this->id . 'lt', time () );
		$this->down = 1;
	}
	
	/**
	 * 调用成功。
	 */
	public function setOk() {
		xcache_set ( $this->id . 'ft', 0 );
		xcache_set ( $this->id . 'lt', time() );
	}
}