php模板引擎

## tmd_tpl.php
	一个国产开源的PHP模板引擎，简单高效，也适合学习与改造。
	来源：http://www.tmdphp.com/
	
## tmd_tpl1.php
	基于tmd_tpl修改，主要是将引用的include/require的模板文件合并在主文件里。
	比如，mail.tpl里包含header.tpl,content.tpl,footer.tpl，生成缓存的时候，在mail.tpl一个文件里会包含header.tpl,content.tpl,footer.tpl的内容。