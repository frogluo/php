php模板引擎

## tmd_tpl.php
	一个国产开源的PHP模板引擎，简单高效，也适合学习与改造。
	来源：http://www.tmdphp.com/
	
## tmd_tpl1.php
	基于tmd_tpl修改，增加功能特性如下：
	1.增加assignObj($obj)方法。这样把整个controller/action对象塞进去，就不需要每个变量都assign，如
	$name = 'frogluo';
	$age = 32;
	$uid = 123456;
	$is_admin = 0;
	$this->view->assign('name',$name);
	$this->view->assign('age',$age);
	$this->view->assign('uid',$uid);
	$this->view->assign('is_admin',$is_admin);
	
	改成：
	$this->name = 'frogluo';
	$this->age = 32;
	$this->uid = 123456;
	$this->is_admin = 0;
	$this->display();
	
	/**
	* 父类先封装一个方法，当调用display时才初始化视图
	*/
	protected function display($tpl = '') {
		$this->view = new tmd_tpl ();
		$this->view->tpl_dir = PATH_PAGE_VIEW;
		$this->view->cache_dir = PATH_PAGE_CACHE;
		$this->view->cache_time = TPL_CACHE_TIME;
		$this->view->my_rep = array (
				'~__ROOT__~' => URL_HOST,
				'~__JSPATH__~' => URL_JS,
				'~__CSSPATH__~' => URL_CSS,
				'~__PLUGINPATH__~' => URL_PLUGIN,
				'~__IMAGEPATH__~' => URL_IMAGE,
				'~__VERSION__~' => UPDATE_TIME 
		);
		$this->view->assignobj ( $this );
		
		if ($tpl) {
			$this->view->display ( $tpl );
		} else {
			if (! $this->page_tpl) {
				$this->page_tpl = 'index.html';
			}
			$this->view->display ( $this->page_frame );
		}
		exit ();
	}
	
	2.将引用的include/require的模板文件合并在主文件里。
	比如，mail.tpl里包含header.tpl,content.tpl,footer.tpl，
	生成缓存的时候，在mail.tpl一个文件里会包含header.tpl,content.tpl,footer.tpl的内容。
	这样做，提升一些性能，特别是页面分得比较零碎的时候。