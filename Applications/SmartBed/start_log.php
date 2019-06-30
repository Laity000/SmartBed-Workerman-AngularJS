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

//global $logger_worker;
$logger_worker = new Worker("Websocket://0.0.0.0:2207");
$logger_worker->name = 'LoggerServer';
$logger_worker->count = 1;

// 调用类的静态方法。
$logger_worker->onWorkerStart = array('LoggerServer', 'onWorkerStart');
$logger_worker->onConnect     = array('LoggerServer', 'onConnect');
$logger_worker->onMessage     = array('LoggerServer', 'onMessage');
$logger_worker->onClose       = array('LoggerServer', 'onClose');
$logger_worker->onWorkerStop  = array('LoggerServer', 'onWorkerStop');

$log = new LoggerServer($logger_worker);

// 如果不是在根目录启动，则运行runAll方法

if (!defined ('GLOBAL_START')) {
    Worker::runAll ();
}