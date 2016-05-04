<?php
class tmd_tpl {
	public $tpl_dir = './tpl/';
	// 模板文件所在目录 注意以斜杠结束
	public $tpl_ext = '';
	// 模板文件扩展名 建议为PHP文件 这样在Dreamweaver中PHP代码才会有高亮效果
	public $cache_dir = './tpl_c/';
	// 模板编译后的缓存目录 一样以斜杠结束 无则自动创建
	public $cache_time = 0;
	// 编译后模板的缓存时间（单位：秒）
	// 0为马上过期 即每次都重新编译
	// -1为永不过期 网站上线后建议设置为-1

	// 自定义的正则替换
	public $my_rep;
	
	// 内置的正则替换
	private $tmd_rep = array (
			'~\{(\$[a-z0-9_]+)\}~i' => '<?php echo isset($1)?$1:\'\'; ?>', // {$name}
			'~\{(\$[a-z0-9_]+)\.([a-z0-9_]+)\}~i' => '<?php echo isset($1[\'$2\'])?$1[\'$2\']:\'\'; ?>', // {$arr.key}
			'~\{(\$[a-z0-9_]+)\[[\'\"]([a-z0-9_]+)[\'\"]\]\}~i' => '<?php echo isset($1[\'$2\'])?$1[\'$2\']:\'\'; ?>', // {$arr.key}
			'~\{(\$[a-z0-9_]+)\.([a-z0-9_]+)\.([a-z0-9_]+)\}~i' => '<?php echo isset($1[\'$2\'][\'$3\'])?$1[\'$2\'][\'$3\']:\'\'; ?>', // {$arr.key.key2}
			'~\{(\$[a-z0-9_]+)\[[\'\"]([a-z0-9_]+)[\'\"]\]\[[\'\"]([a-z0-9_]+)[\'\"]\]\}~i' => '<?php echo isset($1[\'$2\'][\'$3\'])?$1[\'$2\'][\'$3\']:\'\'; ?>', // {$arr.key.key2}
			/**'~<\?php\s+(include_once|require_once|include|require)\s*\(\s*(.+?)\s*\)\s*;?\s*\?>~i' => '<?php include \$this->_include($2) ?>', // ＜?php include('inc/top.php'); ?＞ */
			'~\{:(.+?)\}~' => '<?php echo $1 ?>', // {:strip_tags($a)}
			'~\{\~(.+?)\}~' => '<?php $1 ?>', // {~var_dump($a)}
			'~<\?=\s*~' => '<?php echo ' 
	);
	
	private $merge_reg = array(
		'~<\?php\s+(include_once|require_once|include|require)\s*\(\s*(.+?)\s*\)\s*;?\s*\?>~i' => '<?php include \$this->_include($2) ?>'
	);
	// <?=
	
	// 用于存储模板变量
	public $data = array ();
	
	function __construct() {
	}

	// 赋值
	function assign($name, $value = NULL) {
		$this->data [$name] = $value;
	}
	
	// 将类的公共变量赋值
	function assignObj($obj) {
		$data = get_object_vars ( $obj );
		$this->data = array_merge($this->data, (array)$data);
	}
	// 输出页面
	function display($tpl_file) {
		$_cache_path = $this->cache_path ( $tpl_file );
		if (! $this->is_cached ( $_cache_path )) {
			$this->compile ( $this->tpl_path ( $tpl_file ), $_cache_path );
			$this->merge ( $_cache_path);
		}
		unset ( $tpl_file );
		extract ( $this->data ); // 如果data里有个_cache_path...
		
		include $_cache_path;
	}
	// 返回页面
	function fetch($tpl_file) {
		ob_start ();
		ob_implicit_flush ( 0 );
		$this->display ( $tpl_file );
		return ob_get_clean ();
	}
	// 获取模板文件路径
	private function tpl_path($tpl_file) {
		return $this->tpl_dir . $tpl_file . $this->tpl_ext;
	}
	// 获取模板缓存路径
	private function cache_path($tpl_file) {
		return $this->cache_dir . $tpl_file . $this->tpl_ext;
	}
	// 模板缓存是否有效
	private function is_cached($cache_path) {
		if (! file_exists ( $cache_path )) {
			return false;
		}
		if ($this->cache_time < 0) {
			return true;
		}
		$cache_time = filemtime ( $cache_path );
		if (time () - $cache_time > $this->cache_time) {
			return false;
		}
		return true;
	}
	// 编译模板
	private function compile($tpl_path, $cache_path) {
		$cache = file_get_contents ( $tpl_path );
		if ($cache === FALSE) {
			die ( "模板文件“{$tpl_path}”不存在" );
		}
		
		$tmp = array_merge ( $this->tmd_rep, $this->my_rep );
		$cache = preg_replace ( array_keys ( $tmp ), $tmp, $cache );
		$path = dirname ( $cache_path );
		if (! file_exists ( $path )) {
			mkdir ( $path, 0777, true );
		}
		
		$tmp = file_put_contents ( $cache_path, $cache, LOCK_EX );
		if ($tmp === FALSE) {
			die ( "编译后的模板文件“$cache_path”无法写入" );
		}
	}
	
	// 合并模板文件
	private function merge($cache_tpl) {
		$content = $this->_merge_get_content($cache_tpl);
		$ok = file_put_contents ( $cache_tpl, $content, LOCK_EX );
		if ($ok === FALSE) {
			die ( "编译后的模板文件“$cache_tpl”无法写入" );
		}
	}
	
	private function _merge_get_content($cache_tpl){
		$content = file_get_contents ( $cache_tpl );
		if ($content === FALSE) {
			die ( "模板文件“{$cache_tpl}”不存在" );
		}
		$out = array ();
		preg_match_all ( '~<\?php\s+(include_once|require_once|include|require)\s*\(\s*(.+?)\s*\)\s*;?\s*\?>~i', $content, $out );
		if (count ( $out ) > 0 && count ( $out [2] ) > 0) {
			for($i = 0; $i < count ( $out [2] ); $i ++) {
				if( strpos($out [2] [$i], '$') !== false){
					//if tpl path is var
					$content = preg_replace ( array_keys ( $this->merge_reg ), $this->merge_reg, $content );
					break;
				}else{
					//if tpl path is constant
					$out [2] [$i] = trim ( $out [2] [$i], "'\"" );
					$c = $this->_merge_get_content ( $this->_include( $out [2] [$i] ));
					$content = str_replace ( $out [0] [$i], $c, $content );
				}
			}
		} 
		return $content;
	}
	
	// 页面有include时用到
	private function _include($inc_file) {
		$cache_path = $this->cache_dir . $inc_file;
		$tpl_path = $this->tpl_dir . $inc_file;
		if (! $this->is_cached ( $cache_path )) {
			$this->compile ( $tpl_path, $cache_path );
		}
		return $cache_path;
	}
}
