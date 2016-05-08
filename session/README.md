php session管理框架代码。

自己实现session管理有以下的思路：

1.通过session_set_save_handler注册回调方法，如DBSession

2.对原生机制进行封装，实现start,get,set等方法，如FileSession,MemcacheSession

3.以上1，2两种方法结合，如CookieSession

4.CookieSessionHandler.php，通过实现SessionHandlerInterface接口，PHP5.4后支持。

最好不要直接使用$_SESSION，这样会丧失控制权，难于进一步优化。

对于把session存储到cookie，特别要注意，页面buffer已经关闭的情况下，不能再输出header信息：

 Cannot modify header information - headers already sent


##用法

1.基本用法

$session = Session::factory ('cookie');

//要使用session必先start

//启动后不用再调用

$session->start( );

session->set('name','frogluo');

session->get('name');


$session = Session::factory ('cookie');

session->get('name');


或

$ss = new CookieSession ();

$ss->set('uid',30);

$ss->get('uid');



2.传入已经存在的sid，比如在使用flesh插件上传文件时，可能需要指定sid。

$session = Session::factory ();

$session->start($sid);

session->set('name','frogluo');

session->get('name');
