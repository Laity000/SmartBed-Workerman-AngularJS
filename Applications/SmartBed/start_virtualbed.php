<?php
use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;
require_once __DIR__ . '/../../vendor/autoload.php';

$worker = new Worker();

$worker->onWorkerStart = function($worker){

    

    for ($i=1; $i <= 3; $i++) { 
    	$con = new AsyncTcpConnection('ws://127.0.0.1:8282');
    	$vb = new VirtualBed($con, "admin" .$i);
   		$vb->connect();
    }
  
    
};

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}