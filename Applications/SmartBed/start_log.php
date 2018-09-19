<?php

/**
 * 
 * 启动LOG
 *
 * @author zhangjing
 * @link https://github.com/Laity000
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Workerman\Worker;
use Monolog\Logger;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'LoggerServer.php';


$logger_worker = new Worker("Websocket://0.0.0.0:2207");
$logger_worker->name = 'LoggerServer';
$logger_worker->count = 1;
$logger_worker->onMessage =  function($connection, $data)
{
    var_dump($data);
    $connection->send("hello world");
};

$logger_worker->onMessage = function($connection, $data) {
	echo $data."\n";
	$connection->send($data);

};
		
$logger_worker->onClose = function($connection) {
	$connection->send("bye\n");
};
		
$logger_worker->onConnect = function($connection){
	//echo $connection;
    $connection->send("hello\n");
};

LoggerServer::setWorker($logger_worker);



// 如果不是在根目录启动，则运行runAll方法

if (!defined ('GLOBAL_START')) {
    Worker::runAll ();
}