<?php


use Workerman\Worker;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;


require_once __DIR__ . '/../../vendor/autoload.php';


/**
 * LoggerServer.
 *
 */
class LoggerServer {

	//worker
	private static $logger_worker = null;
	//日志文件缓存
	private static $logerlist = array();
	//收集日志的级别
	public static $logger_level = Logger::INFO;
	//终端显示的级别
	public static $terminal_level = Logger::INFO;
	
	

	public static function initWorker(){

		if (!self::$logger_worker) {
			self::$logger_worker = new Worker("Websocket://0.0.0.0:2207");
			self::$logger_worker->name = 'LoggerServer';
			self::$logger_worker->count = 1;
			self::$logger_worker->onMessage =  function($connection, $data)
			{
    			var_dump($data);
    			$connection->send("hello world");
			};

			self::$logger_worker->onMessage = function($connection, $data) {
				echo $data."\n";
				$connection->send($data);

			};
		}
	}

	public static function setWorker($worker) {

		self::$logger_worker = $worker;
		//var_dump(self::$logger_worker->connections);
	}

	public static function log($echo_level, $logger_message) {

		$logger_time = date('Y-m-d');
		//得到日志句柄
		$logger = self::getLoger($logger_time);
		//记录到日志中
		$logger->addRecord($echo_level, $logger_message);

		self::initWorker();
			//主动推送给其他用户
			
		foreach(self::$logger_worker->connections as $connection){
            /*$connection->send(json_encode ( array (
				'logger_time' => $logger_time,
				'logger_level' => $logger_level,
				'logger_message' => $logger_message 
			)));*/
			$connection->send("hello/n");
			echo "send--------------------";
        	
		}	
		if ($echo_level >= self::$terminal_level) {
			echo "\n". date('Y-m-d H:i:s') ." ". $logger_message;
		}
	}

	//得到日志
	private  static function getLoger($loger_name) {
		if (! array_key_exists ( $loger_name, self::$logerlist )) {
			//把记录写进PHP流，主要用于日志文件。
			$stream = new StreamHandler ( __DIR__ . "/log/$loger_name.log", self::$logger_level );
			//把日志记录格式化成一行字符串。
			$stream->setFormatter ( new LineFormatter () );
			$logger = new Logger ( $loger_name );
			$logger->pushHandler ( $stream );

			//清除日志缓存，因为按时间分组
			self::$logerlist =null;
			self::$logerlist [$loger_name] = $logger;
		}
		return self::$logerlist [$loger_name];
	}
}
