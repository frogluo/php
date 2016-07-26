# HAHttpCall.php
	一个支持多个服务端调用的客户端。
	使用HTTP协议，通用都是用户来调用REST API。
	
	客户端功能类型memcache的客户端，由客户端决定调用的服务端。
	而转发控制类似于nginx反向代理转发，但功能上相对简单。
		
    require 'HAHttpCall.php';
		
    $be1 = new HABackend ();
    $be2 = new HABackend ();
    $be3 = new HABackend ();
		
    $be1->base_url = 'http://localhost:8081/';
    $be2->base_url = 'http://localhost:8082/';
    $be3->base_url = 'http://localhost:8083/';
    $be3->backup = 1;
    $call = new HAHttpCall ();
    $call->format = 'STRING';
    $call->addHABackend ( $be1 ); 
	$call->addHABackend ( $be2 );
    $call->addHABackend ( $be3 );
		
    $ret = $call->get ( 'loadbalance/index' );
    if ($ret) {
        echo $ret;
    } else {
         echo 'fail';
    }
