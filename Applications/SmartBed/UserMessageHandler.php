<?php

use \GatewayWorker\Lib\Gateway;
use \Workerman\Lib\Timer;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * 
 */
class UserMessageHandler
{
	public static function handleMessage($client_id, $message_data, $db)
	{
			//对设备的消息进行分析并作出相应的动作
		switch ($message_data['type']) 
		{	
			case 'BIND':
				self::checkBind($client_id, $message_data);
			break;
			case 'UNBIND':
				self::checkUnbind($client_id);
			break;
			case 'CONTROL_POSTURE':
				self::controlPosture($client_id, $message_data);
			break;
			case 'QUERY_POSTURE':
				self::queryPostureByDB($client_id, $message_data, $db);
			break;
			default:
			case 'QUERY_RECORD':
				self::queryRecord($client_id, $message_data, $db);
			break;
		}

	}

	/**
	 * 检查设备是否连接成功，并发送连接结果
	 * @param string $message_data
	 * @return boolean
	 */
	private static function checkBind($client_id, $message_data)
	{
		
		//当前PID+pid
		$content = $message_data['content'];

		//info
        LoggerServer::log(Utils::INFO, "Server: bind checking Bed[". $content['PID'] ."]...\n");

		//检查content对应指令类型合法性
		if(empty($content))
		{
			//PID+password为空，创建登录失败反馈信息rejected
			self::sendServerFeedback(Utlis::FAIL_BIND_PIDNULL_CODE, Utlis::FAIL_BIND_PIDNULL_TEXT);
			//info
        	LoggerServer::log(Utils::INFO, "User[". $client_id ."]: ". Utlis::FAIL_BIND_PIDNULL_TEXT ."\n");
			return false;
		}
		if (count($content) != 1)
		{
			self::sendServerFeedback(Utlis::FAIL_ILLEGAL_INSTRUIONS_CODE, Utlis::FAIL_ILLEGAL_INSTRUIONS_TEXT);
			//info
        	LoggerServer::log(Utils::INFO, "Server: ". Utlis::FAIL_ILLEGAL_INSTRUIONS_TEXT ." Length of BIND instruction is not 1.\n");
			return false;
		}

		//设备PID
		$PID = $content['PID'];
		//PID设备是否在线
		//TODO: 在绑定的时候也可以不需要查看设备是否在线，而是查看数据库中是否有该PID
		if (self::isBedOnline($PID)) {
			//PID绑定
			Gateway::bindUid($client_id, $PID);
			$_SESSION['boundPID'] = $PID;
			self::sendServerFeedback(Utils::SUCCESS_BIND_CODE, Utils::SUCCESS_BIND_TEXT);
			//info
        	LoggerServer::log(Utils::INFO, "Server: ". "User[". $client_id ."]: ". Utils::SUCCESS_BIND_TAG ."\n");
			//增加指令发送标志位
			//$_SESSION['sendTag'] = false;
			return true;
		}else{
			//绑定的PID设备不在线 
			self::sendServerFeedback(Utils::FAIL_BIND_OFFLINE_CODE, Utils::FAIL_BIND_OFFLINE_TEXT);
			//info
        	LoggerServer::log(Utils::INFO, "User[". $client_id ."]: ". Utils::FAIL_BIND_OFFLINE_TAG ."\n");
			return false;
		}
				
	}

	/**
	 * 发送解除绑定信息
	 * @param string $client_id
	 * @return 
	 */
	private static function checkUnbind($client_id){
		Gateway::unbindUid($client_id, null);
		$_SESSION['boundPID'] = null;
		self::sendServerFeedback(Utils::SUCCESS_UNBIND_CODE, Utils::SUCCESS_UNBIND_TEXT);
		//info
        LoggerServer::log(Utils::INFO, "User[". $client_id ."]: ". Utils::SUCCESS_UNBIND_TAG ."\n");
	}

	/**
	 * 发送服务器反馈信息
	 * @param string $code
	 * @param string $text
	 * @return boolean
	 */
	private static function sendServerFeedback($code, $text){
			
		$new_message = array('type' => 'SERVER_FEEDBACK', 'from' => 'SERVER', 'content' => array($code => $text));
		Gateway::sendToCurrentClient(json_encode($new_message));
		//echo "Server:(send feedback) ". $text ."[". $code ."]\n";
	}

	/**
	 * 发送控制姿态
	 * @param string $client_id
	 * @param string $message_data
	 * @return bool
	 */
	private static function controlPosture($client_id, $message_data)
	{

		//得到设备PID
		//$boundPID = Gateway::getUidByClientId($client_id);
		$boundPID = $_SESSION['boundPID'];

		//info
        LoggerServer::log(Utils::INFO, "User[". $client_id ."]: send control_posture to Bed[". $boundPID ."]...\n");
		
		$angle = intval(reset($message_data['content']));
		$pos = key($message_data['content']);
		//检查角度是否正确
		if ($pos != 'reset' && ($angle < 0 || $angle > 90)) {
			self::sendServerFeedback(Utils::FAIL_CONTROLPOSTURE_ANGLE_CODE, Utils::FAIL_CONTROLPOSTURE_ANGLE_TEXT);
			//info
        	LoggerServer::log(Utils::INFO, "Server: ". Utils::FAIL_CONTROLPOSTURE_ANGLE_TAG ." \n");
			return false;
		}
		switch ($pos) {
			case 'reset':
				$new_package = array('length' => 3, 'type' => Utils::CONTROL_POSTURE, 'pos' => 0, 'angle' => $angle);
				break;
			case 'head':
				$new_package = array('length' => 3, 'type' => Utils::CONTROL_POSTURE, 'pos' => 1, 'angle' => $angle);
				break;
			case 'leg':
				$new_package = array('length' => 3, 'type' => Utils::CONTROL_POSTURE, 'pos' => 2, 'angle' => $angle);
				break;
			case 'left':
				$new_package = array('length' => 3, 'type' => Utils::CONTROL_POSTURE, 'pos' => 3, 'angle' => $angle);
				break;
			case 'right':
				$new_package = array('length' => 3, 'type' => Utils::CONTROL_POSTURE, 'pos' => 4, 'angle' => $angle);
				break;
			case 'lift':
				$new_package = array('length' => 3, 'type' => Utils::CONTROL_POSTURE, 'pos' => 5, 'angle' => $angle);
				break;
			case 'before':
				$new_package = array('length' => 3, 'type' => Utils::CONTROL_POSTURE, 'pos' => 6, 'angle' => $angle);
				break;
			case 'after':
				$new_package = array('length' => 3, 'type' => Utils::CONTROL_POSTURE, 'pos' => 7, 'angle' => $angle);
				break;
			default:
				self::sendServerFeedback(Utils::FAIL_CONTROLPOSTURE_POS_CODE, Utils::FAIL_CONTROLPOSTURE_POS_TEXT);
				//info
        		LoggerServer::log(Utils::INFO, "Server: ". Utils::FAIL_CONTROLPOSTURE_POS_TAG ."\n");
				return false;
		}

		//得到设备id
		$bed_id = self::getBedID($client_id);
			if (empty($bed_id)) {
				return false;
		}

		//检查设备是否在工作
		if (!empty(Gateway::getSession($bed_id)['control_posture_timerid'])) {
			self::sendServerFeedback(Utils::FAIL_CONTROLPOSTURE_WORKING_CODE, Utils::FAIL_CONTROLPOSTURE_WORKING_TEXT);
			//info
        	LoggerServer::log(Utils::INFO, "Server: ". Utils::FAIL_CONTROLPOSTURE_WORKING_TAG ." \n");
			return false;
		}

		//发送控制姿态
		switch (Gateway::getSession($bed_id)['port']) {
			case 8283:
				Gateway::sendToClient($bed_id, $new_package);
				break;
			case 8282:
				Gateway::sendToClient($bed_id, json_encode($message_data));
				break;
			default:
				//info
        		LoggerServer::log(Utils::INFO, "User[". $client_id ."]: send controll_posture error!\n");
		}
		//info
        LoggerServer::log(Utils::INFO, "Server: ". Utils::SUCCESS_CONTROLPOSTURE_TAG ."\n");

		//更新设备session中的用户id
		Gateway::updateSession($bed_id, array('control_posture_userid' => $client_id));

		//反馈失败计数器
		$duration = 120;
		$timer_id = Timer::add($duration, function($bed_id, $client_id)use(&$timer_id){
            if (Gateway::getSession($bed_id)['control_posture_timerid'] == $timer_id) {
				$new_message = array('type' => 'SERVER_FEEDBACK', 'from' => 'SERVER', 'content' => array(Utils::FAIL_CONTROLPOSTURE_TIMEOUT_CODE => Utils::FAIL_CONTROLPOSTURE_TIMEOUT_TEXT));
				Gateway::sendToClient($client_id, json_encode($new_message));
				//超时释放设备session中的用户id和计时器id
				Gateway::updateSession($bed_id, array('control_posture_timerid' => null));
				Gateway::updateSession($bed_id, array('control_posture_userid' => 'unknown'));
				//info
        		LoggerServer::log(Utils::INFO, "Server: ". Utils::FAIL_CONTROLPOSTURE_TIMEOUT_TAG ."\n");
			}
        }, array($bed_id, $client_id), false); 

        //更新设备session中的计时器id
		Gateway::updateSession($bed_id, array('control_posture_timerid' => $timer_id));
		
	}


	/**
	 * 向设备查询姿态
	 * @param string $client_id
	 * @param string $message_data
	 * @return bool
	 */
	private static function queryPosture($client_id, $message_data, $db)
	{
		//得到设备PID
		//$boundPID = Gateway::getUidByClientId($client_id);
		$boundPID = $_SESSION['boundPID'];

		//info
        LoggerServer::log(Utils::INFO, "User[". $client_id ."]: send query_posture to Bed[". $boundPID ."]...\n");

		//得到设备pid
		$bed_id = self::getBedID($client_id);
		if (empty($bed_id)) {
			return false;
		}
		//发送控制姿态	
		switch (Gateway::getSession($bed_id)['port']) {
			case 8283:
				$new_package = array('length' => 1, 'type' => Utils::QUERY_POSTURE );
				//Gateway::sendToClient($bed_id, $new_package);
				
				/*
				//测试设备并行解析：时间必须大于0.2s
				// 计数
    			$count = 1;
    			// 要想$timer_id能正确传递到回调函数内部，$timer_id前面必须加地址符 &
    			$timer_id = Timer::add(0.1, function()use(&$timer_id, &$bed_id, &$new_package, &$count)
    			{
        			echo "Timer run $count\n";
        			Gateway::sendToClient($bed_id, $new_package);
        			// 运行10次后销毁当前定时器
        			if($count++ >= 10)
        			{
            			Timer::del($timer_id);
            			
        			}
    			});
				*/
			break;
			case 8282:
				//echo $bed_id;
				//var_export(Gateway::getAllClientSessions());
				Gateway::sendToClient($bed_id, json_encode($message_data));
			break;
			default:
				//info
        		LoggerServer::log(Utils::INFO, "User[". $client_id ."]: send query_posture error!\n");
		}
		return true;
	}

	/**
	 * 向数据库查询姿态
	 * @param string $client_id
	 * @param string $message_data
	 * @return bool
	 */
	private static function queryPostureByDB($client_id, $message_data, $db)
	{
		//得到设备PID
		//$boundPID = Gateway::getUidByClientId($client_id);
		$boundPID = $_SESSION['boundPID'];

		//info
        LoggerServer::log(Utils::INFO, "User[". $client_id ."]: query posture to database Bed[". $boundPID ."]...\n");

		//得到设备PID
		//$boundPID = Gateway::getUidByClientId($client_id);
		$boundPID = $_SESSION['boundPID'];

		//向数据库查询实时姿态
		$bed_current = $db->select('current_head AS head, current_leg AS leg, current_left AS left, current_right AS right,current_lift AS lift, current_before AS before, current_after AS after, time')->from('tb_bed_record')->where('pid="' .$boundPID. '"')->row();	
		$new_message = array('type' => 'POSTURE', 'from' => 'SERVER', 'content' => $bed_current);
		Gateway::sendToCurrentClient(json_encode($new_message));
		return true;
	}

	private static function queryRecord($client_id, $message_data, $db){
		
		
		
		//得到设备PID
		//$boundPID = Gateway::getUidByClientId($client_id);
		$boundPID = $_SESSION['boundPID'];

		//info
        LoggerServer::log(Utils::INFO, "User[". $client_id ."]: query record bed[". $boundPID ."]...\n");
		
		//检查用户是否绑定了PID
		if(empty($boundPID) || $boundPID == null){
			//未绑定PID
			self::sendServerFeedback(Utils::FAIL_UNBOUND_CODE, Utils::FAIL_UNBOUND_TEXT);
			//info
        	LoggerServer::log(Utils::INFO, "Server: ". Utils::FAIL_UNBOUND_TAG ." \n");
			return false;
		}

		//消息content的key和value
		$key = key($message_data['content']);
		$value = reset($message_data['content']);
		switch ($key) {
			case 'date':
				// 查询所有日期
				$sql = 'SELECT distinct date(time) AS date, datediff(curdate(),date(time)) AS todate
				FROM `tb_posture_record` 
				WHERE pid="' .$boundPID.
				'" ORDER BY date desc';
				$dates = $db->query($sql);
				//echo json_encode($dates);
				/*if (empty($dates)) {
					$dates[0]['date'] = '暂无数据';
				}*/
				$new_message = array('type' => 'RECORD', 'from' => 'dates', 
						'content' => $dates);
				Gateway::sendToCurrentClient(json_encode($new_message));

			break;
			case 'posture':
				if (!empty($value)) {
					$sql = 'SELECT *
					FROM `tb_posture_record` 
					WHERE pid="' .$boundPID. '" and date(time) = "' .$value.
					'" ORDER BY time desc';
					$postures = $db->query($sql);
					$new_message = array('type' => 'RECORD', 'from' => 'postures', 
						'content' => $postures);
					Gateway::sendToCurrentClient(json_encode($new_message));
				}
			break;		
			default:
				//info
        		LoggerServer::log(Utils::INFO, "User[". $client_id ."]: send query_record error!\n");
		}
		
	}


	/**
	 * 设备是否在线
	 * @param string $PID
	 * @return bool
	 */
	private static function isBedOnline($PID){
		//设备集session
		$bed_sessions = Gateway::getAllClientSessions();
		//检查PID绑定设备是否在线
		foreach ($bed_sessions as $temp_client_id => $temp_sessions) 
		{

			if(!empty($temp_sessions['PID']) && $temp_sessions['PID'] == $PID)
			{
				return true;
			}
			
		}
		return false;
	}

	/**
	 * 得到设备id
	 * @param string $user_id
	 * @return string $cliend_id
	 */
	private static function getBedID($user_id){

		//检查用户是否绑定了PID
		//$boundPID = Gateway::getUidByClientId($user_id);
		$boundPID = $_SESSION['boundPID'];
		if(empty($boundPID) || $boundPID == null){
			//未绑定PID
			self::sendServerFeedback(Utils::FAIL_UNBOUND_CODE, Utils::FAIL_UNBOUND_TEXT);
			//info
			echo "Server: ". Utils::FAIL_UNBOUND_TAG ." \n";
			return null;
		}
		//设备集session
		$bed_sessions = Gateway::getAllClientSessions();
		//检查设备是否在线
		foreach ($bed_sessions as $temp_client_id => $temp_sessions) 
		{

			if(!empty($temp_sessions['PID']) && $temp_sessions['PID'] == $boundPID)
			{
				return $temp_client_id;
			}
			
		}
		self::sendServerFeedback(Utils::FAIL_OFFLINE_CODE, Utils::FAIL_OFFLINE_TEXT);
	
		//info
        LoggerServer::log(Utils::INFO, "Server: ". Utils::FAIL_OFFLINE_TAG ." \n");
		return null;
	}

}