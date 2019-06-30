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
	private $logger_worker = null;
	//日志文件缓存
	private static $logerlist = array();
	//收集日志的级别
	public static $logger_level = Logger::INFO;
	//终端显示的级别
	public static $terminal_level = Logger::INFO;
	
	

	
    /**
     * Construct.
     *
     * @param Worker $worker
     * @throws Exception
     */
    /*public function __construct($worker){
    	$this->logger_worker = $worker;
    }*/

    public static function onConnect($connection){}
    public static function onMessage($connection, $message) {
    	echo $message."\n";
		$connection->send($message);
    }
    public static function onClose($connection){}
    public static function onWorkerStop($connection){}


	public static function log($echo_level, $logger_message) {

		$logger_time = date('Y-m-d');
		//得到日志句柄
		$logger = self::getLoger($logger_time);
		//记录到日志中
		$logger->addRecord($echo_level, $logger_message);	
			
		//终端显示
		if ($echo_level >= self::$terminal_level) {
			echo "\n". date('Y-m-d H:i:s') ." ". $logger_message;
		}

		//发送log服务器
		/*if (isset($logger_worker)) {
			foreach($logger_worker->connections as $connection){
            	$connection->send(json_encode ( array (
					'logger_time' => $logger_time,
					'logger_level' => $logger_level,
					'logger_message' => $logger_message 
				)));
				$connection->send("hello/n");
				echo "send--------------------";
        	
			}
		}else{
			echo "false";
		}*/
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
