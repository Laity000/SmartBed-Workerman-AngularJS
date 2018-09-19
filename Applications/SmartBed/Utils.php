<?php
//require_once __DIR__ . '/../../vendor/autoload.php';
class Utils
{

	///////////设备端口

	/**
	 * 接收类型消息
	 */
	const PING = 0X00;
	const CONNECT = 0x01;
	const DISCONNECT = 0x02;
	const POSTURE = 0x03;
	const DONE = 0x04;
	const UNDONE = 0x05;
	/**
	 * 发送类型消息
	 */
	const QUERY_POSTURE = 0x11;
	const QUERY_PID = 0x12;
	const CONTROL_POSTURE = 0x13;
	const SERVER_FEEDBACK_SUCCESS = 0x14;
	const SERVER_FEEDBACK_FAIL = 0x15;

	//////////用户端口
	/**
	 * 检测
	 */
	const SUCCESS_COMM_CODE = "000";
	const SUCCESS_COMM_TAG = "Communication successful.";
	const SUCCESS_COMM_TEXT = "通信成功";

	const FAIL_ILLEGAL_INSTRUTIONS_CODE = "001";
	const FAIL_ILLEGAL_INSTRUIONS_TAG = "illegal instructions!";
	const FAIL_ILLEGAL_INSTRUIONS_TEXT = "指令异常";

	const FAIL_UNBOUND_CODE = "002";
	const FAIL_UNBOUND_TAG = "PID is unbound!";
	const FAIL_UNBOUND_TEXT = "设备未绑定";

	const FAIL_OFFLINE_CODE = "003";
	const FAIL_OFFLINE_TAG = "bed is offline!";
	const FAIL_OFFLINE_TEXT = "设备已下线";

	/**
	 * 绑定
	 */
	const SUCCESS_BIND_CODE = "010";
	const SUCCESS_BIND_TAG = "bind successful.";
	const SUCCESS_BIND_TEXT = "绑定成功";

	const FAIL_BIND_PIDNULL_CODE = "011";
	const FAIL_BIND_PIDNULL_TAG = "bind failed! PID is null.";
	const FAIL_BIND_PIDNULL_TEXT = "绑定失败！PID不正确";

	const FAIL_BIND_OFFLINE_CODE = "012";
	const FAIL_BIND_OFFLINE_TAG = "bind failed! Bed is offline.";
	const FAIL_BIND_OFFLINE_TEXT = "绑定失败！设备不在线";

	/**
	 * 解除绑定
	 */
	const SUCCESS_UNBIND_CODE = "020";
	const SUCCESS_UNBIND_TAG = "unbind successful.";
	const SUCCESS_UNBIND_TEXT = "解除绑定成功";

	const FAIL_UNBIND_CODE = "021";
	const FAIL_UNBIND_TAG = "unbind fail!";
	const FAIL_UNBIND_TEXT = "解除绑定失败";

	/**
	 * 控制姿态
	 */
	const SUCCESS_CONTROLPOSTURE_CODE = "030";
	const SUCCESS_CONTROLPOSTURE_TAG = "forwarding control_posture successful. ";
	const SUCCESS_CONTROLPOSTURE_TEXT = "调整姿态成功 ";


	const FAIL_CONTROLPOSTURE_POS_CODE = "031";
	const FAIL_CONTROLPOSTURE_POS_TAG = "forwarding control_posture fail! pos is error.";
	const FAIL_CONTROLPOSTURE_POS_TEXT = "调整姿态失败！姿态位不正确";

	const FAIL_CONTROLPOSTURE_ANGLE_CODE = "032";
	const FAIL_CONTROLPOSTURE_ANGLE_TAG = "forwarding control_posture fail! angle is error.";
	const FAIL_CONTROLPOSTURE_ANGLE_TEXT = "调整姿态失败！角度不正确";

	const FAIL_CONTROLPOSTURE_WORKING_CODE = "033";
	const FAIL_CONTROLPOSTURE_WORKING_TAG = "bed is working!";
	const FAIL_CONTROLPOSTURE_WORKING_TEXT = "设备正在工作中，请稍后..";

	const FAIL_CONTROLPOSTURE_TIMEOUT_CODE = "034";
	const FAIL_CONTROLPOSTURE_TIMEOUT_TAG = "posture feedback timeout.";
	const FAIL_CONTROLPOSTURE_TIMEOUT_TEXT = "姿态反馈超时";

	//logger
	//
	//
	/**
	 * Detailed debug information
	 */
	const DEBUG = 100;
	
	/**
	 * Interesting events
	 *
	 * Examples: User logs in, SQL logs.
	 */
	const INFO = 200;
	
	/**
	 * Uncommon events
	 */
	const NOTICE = 250;
	
	/**
	 * Exceptional occurrences that are not errors
	 *
	 * Examples: Use of deprecated APIs, poor use of an API,
	 * undesirable things that are not necessarily wrong.
	 */
	const WARNING = 300;
	
	/**
	 * Runtime errors
	 */
	const ERROR = 400;
	
	/**
	 * Critical conditions
	 *
	 * Example: Application component unavailable, unexpected exception.
	 */
	const CRITICAL = 500;
	
	/**
	 * Action must be taken immediately
	 *
	 * Example: Entire website down, database unavailable, etc.
	 * This should trigger the SMS alerts and wake you up.
	 */
	const ALERT = 550;
	
	/**
	 * Urgent alert.
	 */
	const EMERGENCY = 600;

}