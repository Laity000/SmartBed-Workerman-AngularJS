<?php

/**
 * 设备与服务器websocket协议的业务逻辑
 *
 * @author zhangjing
 * @link https://github.com/Laity000
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

use \GatewayWorker\Lib\Gateway;
use \Workerman\Lib\Timer;
require_once __DIR__ . '/../../vendor/autoload.php';

class BedMessageHandler{

    public static function handleMessage($client_id, $message_data, $db){

		//对设备的消息进行分析并作出相应的动作
    switch ($message_data['type']) 
    {
        case 'CONNECT':
        self::checkConnect($message_data);
        break;
        case 'DISCONNECT':
        if(Gateway::isOnline($client_id)){
            if(!empty($_SESSION['PID']))
            {
                //info
                LoggerServer::log(Utils::INFO, "Bed[". $_SESSION['PID'] ."]: disconnecting...\n");
            }
            Gateway::closeClient($client_id);
        }
        break;
        case 'POSTURE':
        self::sendPosture($client_id, $message_data);
        /*
        if (!self::sendPosture($message_data))
        {
          //再次尝试发送
          Timer::add(3, array('BedMessageHandler', 'sendPosture'), array($message_data), false);
        }
        */
        break;

        case 'UNDONE':
            self::sendUndone($client_id, $message_data);
        break;
        case 'DONE':
            self::sendDone($client_id, $message_data, $db);
        default:
        break;
        
        }
    }

	/**
    * 检查设备是否连接成功，并发送连接结果
    * @param string $message_data
    * @return bool
    */
	private static function checkConnect($message_data){

        //info
        LoggerServer::log(Utils::INFO, "Server: connect checking...\n");
     	//当前PID+password
        $content = $message_data['content'];

     	//检查content对应指令类型合法性
        if(empty($content))
        {
        	//PID+password为空，创建登录失败反馈信息rejected
            $new_message = array('type' => 'SERVER_FEEDBACK', 'from' => 'SERVER', 'content' => array('rejected' => 'PID is null.'));
            Gateway::sendToCurrentClient(json_encode($new_message));
        	
            //error
            LoggerServer::log(Utils::ERROR, "Bed[unknown]: connect failed! PID is null.\n");
            Gateway::closeCurrentClient();
            return false;
        }

        if (count($content) != 1)
        {
            
            //error
            LoggerServer::log(Utils::ERROR, "Server: illegal instructions！Length of connect instruction is not 1.\n");
            ateway::closeCurrentClient();
            return false;
        }

     	//设备PID
        $PID = $content['PID'];

        //设备集session
        $bed_sessions = Gateway::getAllClientSessions();
        //检查PID是否重复登录
        foreach ($bed_sessions as $temp_client_id => $temp_sessions) 
        {

            if(!empty($temp_sessions['PID']) && $temp_sessions['PID'] == $PID)
            {
                //用户名重复，创建登录失败反馈信息
                $new_message = array('type' => 'SERVER_FEEDBACK', 'from' => 'SERVER', 'content' => array('rejected' => "PID[". $PID. "] is repeated."));
                Gateway::sendToCurrentClient(json_encode($new_message));
                //error
                LoggerServer::log(Utils::ERROR, "Bed[". $PID ."]: connect failed! PID is repeated.\n");
                Gateway::closeCurrentClient();
                return false;
            }
        }

	    //没有发现重名
        //把PID、password到session中
        $_SESSION['PID'] = $PID;
        //TODO: 检查密码

        //创建连接成功反馈信息
        $new_message = array('type' => 'SERVER_FEEDBACK', 'from' => 'SERVER', 'content' => array('received' => "connect successful! "));
        Gateway::sendToCurrentClient(json_encode($new_message));
        //info
        LoggerServer::log(Utils::INFO, "Bed[". $PID ."]: connect successful!\n");
        //创建
        return true;
    }


    private static function sendPosture($client_id, $message_data){

        //info
        LoggerServer::log(Utils::INFO, "Bed[". $_SESSION['PID'] ."]:send posture info to users...\n");
        if(empty($_SESSION['PID'])){
            
            //error
            LoggerServer::log(Utils::ERROR, "Server: Bed session[PID] lost!\n");
            Gateway::closeClient($client_id);
            return false;
        }else{
            //向绑定的用户发送姿态信息
            Gateway::sendToUid($_SESSION['PID'], json_encode($message_data));
            return true;
        }
    }  


    private static function sendUndone($client_id, $message_data){

        //info
        LoggerServer::log(Utils::INFO, "Bed[". $_SESSION['PID'] ."]:send undone info to users...\n");
        if(empty($_SESSION['PID'])){
            
            //error
            LoggerServer::log(Utils::ERROR, "Server: Bed session[PID] lost!\n");
            Gateway::closeClient($client_id);
            return false;
        }
                
        //向绑定的用户发送未完成信息
        //并释放计时器和设备session中的用户id
        if (!empty($_SESSION['control_posture_timerid'])) {
            Gateway::sendToClient($_SESSION['control_posture_userid'], json_encode($message_data));
            Timer::del($_SESSION['control_posture_timerid']);
            $_SESSION['control_posture_timerid'] = null;
            $_SESSION['control_posture_userid'] = null;
        }else{
            Gateway::sendToUid($_SESSION['PID'], json_encode($message_data));
        }
        return true;
    }


    private static function sendDone($client_id, $message_data, $db){

        //info
        LoggerServer::log(Utils::INFO, "Bed[". $_SESSION['PID'] ."]:send done info to users...\n");
        if(empty($_SESSION['PID'])){
            //error
            LoggerServer::log(Utils::ERROR, "Server: Bed session[PID] lost!\n");
            Gateway::closeClient($client_id);
            return false;
        }


        //向绑定的用户工作完成信息
        $odate = date("Y-m-d H:i:s");
        //echo $odate;
        $message_data['content']['time'] = $odate;
        Gateway::sendToUid($_SESSION['PID'], json_encode($message_data));

        //记录到数据库bed_record的posture
        for ($i=0; $i < 2; $i++) { 
            $row_count = $db->update('tb_bed_record')->cols(array(
                'current_head'=>intval($message_data['content']['head']),
                'current_leg' => intval($message_data['content']['leg']),
                'current_left' => intval($message_data['content']['left']),
                'current_right' => intval($message_data['content']['right']),
                'current_lift' => intval($message_data['content']['lift']),
                'current_before' => intval($message_data['content']['before']),
                'current_after' => intval($message_data['content']['after']),
                'time' => $odate))->where("pid='".$_SESSION['PID']."'")->query();
            if ($row_count) {
                //debug
                LoggerServer::log(Utils::DEBUG, "Server: DB update bed_record posture successful.\n");
                break;   
            }else{
                //error
                LoggerServer::log(Utils::ERROR, "Server: DB update bed_record posture failed!\n");
                //设备不存在则先插入设备pid
                $insert_id = $db->insert('tb_bed_record')->cols(array(
                    'pid' => $_SESSION['PID'],
                    'password' => "admin"))->query();
                if ($insert_id) {
                    //debug
                    LoggerServer::log(Utils::DEBUG, "Server: DB insert bed_record successful.\n");
                }else{
                    //error
                    LoggerServer::log(Utils::ERROR, "Server: DB insert bed_record failed!\n");
                }
            }
        }

        //操作者类型
        if (!empty($_SESSION['control_posture_userid'])) {
            $user_type = 'remote';
        }else{
            $user_type = 'local';
        }
        
        //记录到数据库posture_record
        $insert_id = $db->insert('tb_posture_record')->cols(array(
            'pid' => $_SESSION['PID'],
            'user_type' => $user_type,
            'uid'=> $_SESSION['control_posture_userid'],
            'posture_head' => intval($message_data['content']['head']),
            'posture_leg' => intval($message_data['content']['leg']),
            'posture_left' => intval($message_data['content']['left']),
            'posture_right' => intval($message_data['content']['right']),
            'posture_lift' => intval($message_data['content']['lift']),
            'posture_before' => intval($message_data['content']['before']),
            'posture_after' => intval($message_data['content']['after']),
            'time' => $odate))->query();
        if ($insert_id) {
            //debug
            LoggerServer::log(Utils::DEBUG, "Server: DB insert posture_record successful.\n");
        }else{
            //error
            LoggerServer::log(Utils::ERROR, "Server: DB insert posture_record failed!\n");
        }

        //释放计时器和设备session中的计时器id和用户id
        if (!empty($_SESSION['control_posture_timerid'])) {
            Timer::del($_SESSION['control_posture_timerid']);
            $_SESSION['control_posture_timerid'] = null;
            $_SESSION['control_posture_userid'] = null;
        }

        return true;
    }

    private static function queryPID(){

        $new_message = array('type' => 'QUERY_PID', 'from' => 'SERVER');
        Gateway::sendToCurrentClient(json_encode($new_message));
        //info
        LoggerServer::log(Utils::INFO, "Server: query PID...\n");
    }

}