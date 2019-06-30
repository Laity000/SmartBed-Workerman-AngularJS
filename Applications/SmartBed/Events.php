<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;

require_once __DIR__ . '/../../vendor/autoload.php';
/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
  
    /**
     * 新建一个类的静态成员，用来保存数据库实例
     */
    public static $db = null;

    /**
     * logger
     */
    //public static $logger = null;

    /**
     * 进程启动后初始化数据库连接
     */
    public static function onWorkerStart($worker)
    {
        
        //self::$logger = new LoggerServer('0.0.0.0:2207');

        self::$db = new \Workerman\MySQL\Connection('127.0.0.1', '3306', 'root', '123456', 'db_smartbed');
    }

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    
    public static function onConnect($client_id)
    {
    
        switch ($_SERVER['GATEWAY_PORT']) {
          case 8282: 
              //info
              LoggerServer::log(Utils::INFO, "client:{".$client_id."} connecting...\n");
              break;
          case 8283:
              LoggerServer::log(Utils::INFO, "bed:{".$client_id."} connecting...\n");
              BedPackageHandler::queryPID();
              break;
          default:
              //error
              self::$logger->log(Utils::ERROR, "[error_log]: unknown port!\n");
        }
    }

   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $message)
   {
        // debug
        //echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:".json_encode($_SESSION)."\n";
        switch ($_SERVER['GATEWAY_PORT']) {
          case 8282:
            $_SESSION['port'] = 8282;
            self::parseJsonPort($client_id, $message);
            break;
          case 8283:
            $_SESSION['port'] = 8283;
            self::parsePackagePort($client_id, $message);
            break;
          default:
          //error
          self::$logger->log(Utils::ERROR, "[error_log]: unknown port!\n");
        }
    }
      
    /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
    public static function onClose($client_id)
    {
       
        switch ($_SERVER['GATEWAY_PORT']) {
          case 8282: 
              //info
              LoggerServer::log(Utils::INFO, "client:{".$client_id."} disconnecting...\n");
              break;
          case 8283:
              LoggerServer::log(Utils::INFO, "bed:{".$client_id."} disconnecting...\n");
              break;
          default:
              //error
              self::$logger->log(Utils::ERROR, "[error_log]: unknown port!\n");
        }
    }

    /**
    * 处理设备端口发送的消息类型
    *
    */
    private static function parsePackagePort($client_id, $package){
        //debug
        LoggerServer::log(Utils::DEBUG, "package: ". json_encode($package) ."\n");
        BedPackageHandler::handlePackage($client_id, $package, self::$db);
    }

    /**
    * 处理用户端口发送的消息类型
    *
    */
    private static function parseJsonPort($client_id, $message){
        //debug
        LoggerServer::log(Utils::DEBUG, " message: ".$message."\n");
    
        // 客户端传递的是json数据
        $message_data = json_decode($message, true);
        if(!$message_data)
        {
            return;
        }
        //debug
        //echo '$message_data:';var_dump($message_data);
        switch ($message_data['from']) {
          case 'BED':
            BedMessageHandler::handleMessage($client_id, $message_data, self::$db);
            break;
          case 'USER':
            UserMessageHandler::handleMessage($client_id, $message_data, self::$db);
            break;
          default;
            LoggerServer::log(Utils::ERROR, "[". $client_id ."]: unknown sender!\n");
        }
    }
}
