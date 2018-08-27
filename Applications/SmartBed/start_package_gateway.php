<?php
/**
 * 设备链接端口
 *
 * @author zhangjing
 * @link https://github.com/Laity000
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */


use \Workerman\Worker;
use \GatewayWorker\Gateway;

// 自动加载类
require_once __DIR__ . '/../../vendor/autoload.php';


// 新增8283端口，开启package自定义数据协议
$gateway_package = new Gateway("Package://0.0.0.0:8283");
// 进程名称，主要是status时方便识别
$gateway_package->name = 'SBGatewayPackage';
// 开启多少text协议的gateway进程
$gateway_package->count = 4;
// 本机ip（分布式部署时需要设置成内网ip）
$gateway_package->lanIp = '127.0.0.1';
// 设置服务注册地址(注意：这个地址是start_register.php中监听的地址)
$gateway_package->registerAddress = '127.0.0.1:1236';
// gateway内部通讯起始端口，起始端口不要重复
$gateway_package->startPort = 2500;
// 心跳间隔
$gateway_package->pingInterval = 15;
// 心跳未响应断开时间
$gateway_package->pingNotResponseLimit = 2;
// 心跳数据
//$gateway_package->pingData = array('length' => 1, 'type' => 0x10);
// 服务注册地址
// ##########新增端口支持Package协议 结束##########

if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}