<?php

/**
 * 模拟设备
 *
 * @author zhangjing
 * @link https://github.com/Laity000
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;
use \Workerman\Lib\Timer;
require_once __DIR__ . '/../../vendor/autoload.php';

class VirtualBed
{

	/**
     * websocket connect.
     *
     * @var AsyncTcpConnection
     */
	private $_con = null;

    /**
     * pid.
     *
     * @var string
     */
    private $_pid = null;

	/**
     * 姿态数组.
     *
     * @var array
     */
    private  $_posture = array(
        'head'  => 0,
        'leg'   => 0,
        'left'  => 0,
        'right' => 0,
        'lift'  => 0,
        'before'=> 0,
        'after' => 0
    );

    /**
     * 是否正在工作.
     *
     * @var boolean
     */
    private $_isWorking = false;

    /**
     * Construct.
     *
     * @param AsyncTcpConnection $con
     * @throws Exception
     */
    public function __construct($con, $pid)
    {
    	$this->_con = $con;
        $this->_pid = $pid;

    	$this->_con->onConnect = function($con) {
          
            
            //debug
            LoggerServer::log(Utils::DEBUG, "\n[virtualbed: ". $this->_pid ."] send connect.\n");
            self::send('CONNECT', array('PID' => $this->_pid));  
            
    	};

        $this->_con->onClose = function($con){
            Timer::add(2, function()
            {
                //debug
                LoggerServer::log(Utils::DEBUG, "\n[virtualbed: ". $this->_pid ."] reconnect..\n");
                $con = new AsyncTcpConnection('ws://127.0.0.1:8282');
                $vb = new VirtualBed($con, $this->_pid);
                $vb->connect();
            }, array(),false);
           
        };

    	$this->_con->onMessage = function($con, $data) {
            //debug
            LoggerServer::log(Utils::DEBUG, "\n[virtualbed] received data: ".$data."\n");
        	// 客户端传递的是json数据
        	$message_data = json_decode($data, true);
        	switch($message_data['type']){
            	case 'SERVER_FEEDBACK':
                	self::parseServerFeedback($message_data);
                break;
            	case 'QUERY_POSTURE':
               	 	self::sendPosture();
                break;
            	case 'QUERY_PID':
               	    self::sendPID();
                break;
            	case 'CONTROL_POSTURE':
                	//self::adjustPosture($message_data);
                    if ($this->_isWorking) {
                        self::sendUndone("1");
                    }else{
                        // 正在工作
                        $this->_isWorking = true;
                        Timer::add(5, array($this, 'adjustPosture'), array($message_data), false);
                    }  
                break;
        	}
    	};
    }

    /**
     * 连接
     *
     */
    public function connect(){
    	$this->_con->connect();
    }

    /**
     * 断开连接
     *
     */
    public function disconnect(){
    	$this->_con->disconnect();
    }

    /**
     * 解析服务器反馈消息
     *
     */
    private function parseServerFeedback($message_data){
    	$text = reset($message_data['content']);
		$code = key($message_data['content']);
        //debug
        LoggerServer::log(Utils::DEBUG, "[virtualbed: ". $this->_pid ."] server feedback: ". $text ."[". $code ."]\n");
        
        if ($code != 'rejected') {
        	//断开连接
        }
    }

    /**
     * 控制姿态
     *
     */
    public function adjustPosture($message_data){
    	/*
        self::sendUndone("2");
        //工作完成
        $this->_isWorking = false;
        return false;
		*/
        foreach ($message_data['content'] as $pos => $angle) 
   		{
        	//遍历每个key/value对
            switch($pos){
                case 'reset':
                    $this->_posture['head'] = 0;
        			$this->_posture['leg'] = 0;
        			$this->_posture['left'] = 0;
        			$this->_posture['right'] = 0;
        			$this->_posture['lift'] = 0;
                    $this->_posture['before'] = 0;
                    $this->_posture['after'] = 0;
                break;
                case 'head':
                    $this->_posture['left'] = 0;
                    $this->_posture['right'] = 0;
                    $this->_posture['head'] = intval($angle);
                break; 
                case 'leg':
                    $this->_posture['left'] = 0;
                    $this->_posture['right'] = 0;
                    $this->_posture['leg'] = intval($angle);
                break;     
                case 'left':
                    $this->_posture['head'] = 0;
                    $this->_posture['leg'] = 0;
                    $this->_posture['right'] = 0;
                    $this->_posture['left'] = intval($angle);
                break;  
                case 'right':
                    $this->_posture['head'] = 0;
                    $this->_posture['leg'] = 0;
                    $this->_posture['left'] = 0;
                    $this->_posture['right'] = intval($angle);
                break;  
                case 'lift':
                    $this->_posture['lift'] = intval($angle);
                    $this->_posture['before'] = 0;
                    $this->_posture['after'] = 0;
                break;
                case 'before':
                    $this->_posture['before'] = intval($angle);
                    $this->_posture['lift'] = 0;
                    $this->_posture['after'] = 0;
                break;
                case 'after':
                    $this->_posture['after'] = intval($angle);
                    $this->_posture['lift'] = 0;
                    $this->_posture['before'] = 0;
                break;  
            }  
        } 
        self::send('DONE', $this->_posture);
        
        //debug
        LoggerServer::log(Utils::DEBUG, "[virtualbed: ". $this->_pid ."] send done.\n");
        //工作完成
        $this->_isWorking = false;
        return true;

    }

	/**
     * 发送设备PID
     *
     */
    private function sendPID(){
        self::send('PID', array('PID' => $this->_pid)); 
         
        //debug
        LoggerServer::log(Utils::DEBUG, "[virtualbed] send PID.\n");
    } 

    /**
     * 发送设备PID
     *
     */
    private function sendUndone($tag){
        switch ($tag) {
            case '1':
                self::send('UNDONE', array('2' => '其他用户操作中，请稍后..'));  
              
                //debug
                LoggerServer::log(Utils::DEBUG, "[virtualbed: ". $this->_pid ."] send remote undone.\n");
            break;

            case '2':
                self::send('UNDONE', array('2' => '手控盒操作中，请稍后..'));  

                //debug
                LoggerServer::log(Utils::DEBUG, "[virtualbed: ". $this->_pid ."] send local undone.\n");
            break;
            
            default:
            # code...
            break;
        }
    } 

	/**
     * 发送设备姿态消息
     *
     */
    private function sendPosture(){
       	self::send('POSTURE', $this->_posture);
        
        //debug
        LoggerServer::log(Utils::DEBUG, "[virtualbed: ". $this->_pid ."] send posture.\n");
    }

    /**
     * 发送消息
     *
     * @param string $type
     * @param array $content
     */
    private function send($type, $content){
        $new_message = array('type' => $type, 'from' => 'BED', 'content' => $content);
		$this->_con->send(json_encode($new_message));
		
        //debug
        LoggerServer::log(Utils::DEBUG, "\n[virtualbed: ". $this->_pid ."] send data: ". json_encode($new_message) ."]\n");
    }

}