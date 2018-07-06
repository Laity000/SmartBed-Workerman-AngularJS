<?php

use \GatewayWorker\Lib\Gateway;
use \Workerman\Lib\Timer;
require_once __DIR__ . '/../../vendor/autoload.php';

class BedPackageHandler
{
	public static function handlePackage($client_id, $package_data, $db)
	{

		//对设备的数据包进行分析并作出相应的动作
    	switch ($package_data['type']) {
    		case Utils::CONNECT:
    			self::checkConnect($package_data);
    		break;
    		case Utils::DISCONNECT:
      			if(Gateway::isOnline($client_id)){
       				if(!empty($_SESSION['PID']))
        			{
          				echo "Bed[". $_SESSION['PID'] ."]: disconnecting...\n";
       				}
        			Gateway::closeClient($client_id);
      			}
     		break;
     		case Utils::POSTURE:
      			self::sendPosture($client_id, $package_data);
      		break;
      		case Utils::DONE:
      			self::sendDone($client_id, $package_data, $db);
      		break;
      		case Utils::UNDONE:
      			self::sendUndone($client_id, $package_data);
      		break;
    	}
    }

    /**
   	* 检查设备是否连接成功，并发送连接结果
   	* @param string $package_data
   	* @return bool
   	*/
	private static function checkConnect($package_data)
 	{
    	echo "Server: connect checking...\n";
    
    	
     	//检查PID
    	if(empty($package_data['PID']))
    	{
        	//PID+password为空，创建登录失败反馈信息rejected
     		$new_package = array('length' => 1, 'type' => Utils::SERVER_FEEDBACK_FAIL);
      		Gateway::sendToCurrentClient($new_package);
        	//info
     		echo "Bed[unknown]: connect failed! PID is null.\n";
     		Gateway::closeCurrentClient();
     		return false;
   		}
   		if (strlen($package_data['PID']) != 6)
   		{
   			$new_package = array('length' => 1, 'type' => Utils::SERVER_FEEDBACK_FAIL);
      		Gateway::sendToCurrentClient($new_package);
     		echo "Server: illegal instructions！Length of PID is not 6 byte.";
     		Gateway::closeCurrentClient();
     		return false;
   		}

     	//设备PID
   		$PID = trim($package_data['PID']);

      	//设备集session
   		$bed_sessions = Gateway::getAllClientSessions();
      	
      	//检查PID是否重复登录
   		foreach ($bed_sessions as $temp_client_id => $temp_sessions) 
   		{

    		if(!empty($temp_sessions['PID']) && $temp_sessions['PID'] == $PID)
    		{
          		//用户名重复，创建登录失败反馈信息
      			$new_package = array('length' => 1, 'type' => Utils::SERVER_FEEDBACK_FAIL);
      			Gateway::sendToCurrentClient($new_package);
          		//info
      			echo "Bed[". $PID ."]: connect failed! PID is repeated.\n";
      			//Gateway::closeCurrentClient();
      			return false;
    		}
  		}

		//没有发现重名
  		//把PID、password到session中
  		$_SESSION['PID'] = $PID;
      	//TODO: 检查密码
  		//$_SESSION['password'] = $password;

  		//创建连接成功反馈信息
  		$new_package = array('length' => 1, 'type' => Utils::SERVER_FEEDBACK_SUCCESS);
      	Gateway::sendToCurrentClient($new_package);
  		//info
  		echo "Bed[". $PID ."]: connect successful!\n";
  		return true;
	}

	private static function sendPosture($client_id, $package_data)
	{
  		echo "Bed[". $_SESSION['PID'] ."]:send posture info to users...\n";  
  		if(empty($_SESSION['PID'])){
    		echo "Server: Bed session[PID] lost!\n";
    		Gateway::closeClient($client_id);
    		return false;
  		}else{
          	//向绑定的用户发送姿态信息
          	$new_message = array('type' => 'POSTURE', 'from' => 'SERVER', 'content' => array('head' => $package_data['head'], 'leg' => $package_data['leg'], 'left' => $package_data['left'], 'right' => $package_data['right'], 'lift' => $package_data['lift']));
    		Gateway::sendToUid($_SESSION['PID'], json_encode($new_message));
    		return true;
  		}
	}

	private static function sendDone($client_id, $package_data, $db)
	{
 		echo "Bed[". $_SESSION['PID'] ."]:send done info to users...\n";
  		if(empty($_SESSION['PID'])){
    	echo "Server: Bed session[PID] lost!\n";
    	Gateway::closeClient($client_id);
    	return false;
  		}else{
          	//向绑定的用户工作完成信息
          	$new_message = array('type' => 'DONE', 'from' => 'SERVER', 'content' => array('head' => $package_data['head'], 'leg' => $package_data['leg'], 'left' => $package_data['left'], 'right' => $package_data['right'], 'lift' => $package_data['lift']));
    		Gateway::sendToUid($_SESSION['PID'], json_encode($new_message));
          	//记录到数据库
    		$odate = date("Y-m-d H:i:s");
    		$insert_id = $db->insert('tb_posture_record')->cols(array(
      		'pid' => $_SESSION['PID'],
      		'uid'=> "",
      		'posture_head' => $package_data['head'],
      		'posture_leg' => $package_data['leg'],
      		'posture_left' => $package_data['left'],
      		'posture_right' => $package_data['right'],
      		'posture_lift' => $package_data['lift'],
      		'time' => $odate))->query();
    		if ($insert_id) {
      			echo "Server: DB insert posture record successful.\n";
    		}else{
      			echo "Server: DB insert posture record failed!\n";
    		}
    		return true;
  		}
	}

	private static function sendUndone($client_id, $package_data)
	{
  		echo "Bed[". $_SESSION['PID'] ."]:send undone info to users...\n";
  		if(empty($_SESSION['PID'])){
    		echo "Server: Bed session[PID] lost!\n";
    		Gateway::closeClient($client_id);
    		return false;
  		}else{
          	//向绑定的用户未完成信息
          	$new_message = array('type' => 'UNDONE', 'from' => 'SERVER');
   			Gateway::sendToUid($_SESSION['PID'], json_encode($new_message));
    		return true;
  		}
	}
}