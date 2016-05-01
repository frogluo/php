php session管理框架代码。

有以下的思路：

1.通过session_set_save_handler注册回调方法，如DBSession

2.自己实现start,get,set等方法，如FileSession,MemcacheSession

3.以上1，2两种方法结合，如CookieSession


##用法

1.基本用法

$session = Session::factory ('cookie');

$session->start( );//启动后不用再调用

session->sset('name','frogluo');

session->sget('name');


$session = Session::factory ('cookie');

session->sget('name');


或

$ss = new CookieSession ();

$ss->sset('uid',30);

$ss->sget('uid');



2. 传入已经存在的sid，比如在使用flesh插件上传文件时，可能需要指定sid。

$session = Session::factory ();

$session->start($sid);

session->sset('name','frogluo');

session->sget('name');
