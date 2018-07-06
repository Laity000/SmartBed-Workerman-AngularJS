<?php

use \GatewayWorker\Lib\Gateway;
use \Workerman\Lib\Timer;
//require_once("Utils.php");
require_once __DIR__ . '/../../vendor/autoload.php';

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
				self::queryPosture($client_id, $message_data);
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
		echo "Server: bind checking...\n";
		//当前PID+pid
		$content = $message_data['content'];

		//检查content对应指令类型合法性
		if(empty($content))
		{
			//PID+password为空，创建登录失败反馈信息rejected
				self::sendServerFeedback(Utlis::FAIL_BIND_PIDNULL_CODE, Utlis::FAIL_BIND_PIDNULL_TEXT);
				//info
				echo "User[". $client_id ."]: ". Utlis::FAIL_BIND_PIDNULL_TEXT ."\n";
				return false;
		}
		if (count($content) != 1)
		{
			self::sendServerFeedback(Utlis::FAIL_ILLEGAL_INSTRUIONS_CODE, Utlis::FAIL_ILLEGAL_INSTRUIONS_TEXT);
			//info
			echo "Server: ". Utlis::FAIL_ILLEGAL_INSTRUIONS_TEXT ." Length of BIND instruction is not 1.\n";
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
			echo "User[". $client_id ."]: ". Utils::SUCCESS_BIND_TAG ."\n";
			return true;
		}else{
			//绑定的PID设备不在线 
			self::sendServerFeedback(Utils::FAIL_BIND_OFFLINE_CODE, Utils::FAIL_BIND_OFFLINE_TEXT);
			//info
			echo "User[". $client_id ."]: ". Utils::FAIL_BIND_OFFLINE_TAG ."\n";
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
			echo "User[". $client_id ."]: ". Utils::SUCCESS_UNBIND_TAG ."\n";
	}

	/**
	 * 发送服务器反馈信息
	 * @param string $code
	 * @param string $text
	 * @return boolean
	 */
	private static function sendServerFeedback($code, $text)
	{
			
		$new_message = array('type' => 'SERVER_FEEDBACK', 'from' => 'SERVER', 
						'content' => array($code => $text));
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
		//info
		echo "User[". $client_id ."]: send control_posture to Bed...\n";
		
		$angle = intval(reset($message_data['content']));
		$pos = key($message_data['content']);
		//检查角度是否正确
		if ($pos != 'reset' && ($angle < 0 || $angle > 90)) {
			self::sendServerFeedback(Utils::FAIL_CONTROLPOSTURE_ANGLE_CODE, Utils::FAIL_CONTROLPOSTURE_ANGLE_TEXT);
			//info
			echo "Server: ". Utils::FAIL_CONTROLPOSTURE_ANGLE_TAG ." \n";
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
				$new_package = array('length' => 3, 'type' => Utils::CONTROL_POSTURE, 'pos' => 4, 'angle' => $angle);
				break;
			default:
				self::sendServerFeedback(Utils::FAIL_CONTROLPOSTURE_POS_CODE, Utils::FAIL_CONTROLPOSTURE_POS_TEXT);
				//info
				echo "Server: ". Utils::FAIL_CONTROLPOSTURE_POS_TAG ."\n";
				return false;
		}

		//得到设备id
		$bed_id = self::getBedID($client_id);
			if (empty($bed_id)) {
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
				echo "User[". $client_id ."]: send controll_posture error!\n";
		}
		//info
		echo "Server: ". Utils::SUCCESS_CONTROLPOSTURE_TAG ."\n";
		return true;

	}  

	/**
	 * 查询控制姿态
	 * @param string $client_id
	 * @param string $message_data
	 * @return bool
	 */
	private static function queryPosture($client_id, $message_data)
	{
		//info
		echo "User[". $client_id ."]: send query_posture to Bed...\n";

		//得到设备pid
		$bed_id = self::getBedID($client_id);
		if (empty($bed_id)) {
			return false;
		}
		//发送控制姿态	
		switch (Gateway::getSession($bed_id)['port']) {
			case 8283:
				$new_package = array('length' => 1, 'type' => Utils::QUERY_POSTURE );
				Gateway::sendToClient($bed_id, $new_package);
				break;
			case 8282:
				//echo $bed_id;
				//var_export(Gateway::getAllClientSessions());
				Gateway::sendToClient($bed_id, json_encode($message_data));
				break;
			default:
				echo "User[". $client_id ."]: send query_posture error!\n";
		}
		return true;

	}

	private static function queryRecord($client_id, $message_data, $db){
		//info
		echo "User[". $client_id ."]: query record...\n";
		
		//得到设备PID
		//$boundPID = Gateway::getUidByClientId($client_id);
		$boundPID = $_SESSION['boundPID'];
		
		//检查用户是否绑定了PID
		if(empty($boundPID) || $boundPID == null){
			//未绑定PID
			self::sendServerFeedback(Utils::FAIL_UNBOUND_CODE, Utils::FAIL_UNBOUND_TEXT);
			//info
			echo "Server: ". Utils::FAIL_UNBOUND_TAG ." \n";
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
				$new_message = array('type' => 'RECORD', 'from' => 'SERVER', 
						'content' => array('dates' => $dates));
				Gateway::sendToCurrentClient(json_encode($new_message));

			break;
			case 'posture':
				if (!empty($value)) {
					$sql = 'SELECT posture_head, posture_leg, posture_left, posture_right,posture_lift, time
					FROM `tb_posture_record` 
					WHERE pid="' .$boundPID. '" and date(time) = "' .$value. '"';
					$postures = $db->query($sql);
					$new_message = array('type' => 'RECORD', 'from' => 'SERVER', 
						'content' => array('postures' => $postures));
					Gateway::sendToCurrentClient(json_encode($new_message));
				}

			break;		
			default:
				echo "User[". $client_id ."]: send query_record error!\n";
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
				echo "Server: ". Utils::FAIL_OFFLINE_TAG ." \n";
			return null;
	}

}