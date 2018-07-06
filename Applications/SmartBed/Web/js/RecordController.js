 'use strict';

app.controller('RecordController', function($scope, $stateParams, $location) {
	
	//传参判断
	switch($stateParams.key){
		//只需要首次向数据库查询记录日期
		case 'date':
			if ($stateParams.value == 'first') {
				sendRecord('date', '');
			}
			//显示记录日期模块
			$scope.showDates = true;
			//关闭记录姿态模块
			$scope.showPostures = false;
		break;
		case 'posture':
			//向数据库查询记录姿态
			sendRecord('posture', $stateParams.value);
			//关闭记录日期模块
			$scope.showDates = false;
			//关闭记录姿态模块
			$scope.showPostures = true;
		break;
		
	}
	//返回操作
	$scope.return = function(){
		if ($scope.showDates) {
			//记录日期模块返回主页
			$location.path('home');
		}else{
			//记录姿态模块返回记录日期模块(二次返回不需要访问数据库)
			$location.path('record/date/second');
		}
	}
});