 'use strict';

app.controller('SetupController', function($scope, $location) {
	//初始化设备PID
	$scope.pid = $scope.Messages.getIsBound().PID;
	//绑定成功跳转到msg页面
	$scope.$watch('Messages.getIsBound().tag', function(newValue,oldValue){ 
		if ( oldValue == false && newValue == true) {
			$location.path('msg');
		}
	});
	//绑定操作
	$scope.bind = function(){
		//检查设备是否已经绑定
		if ($scope.Messages.getIsBound().tag == false) {
			if(!$scope.pid) {
      			$.toptip('请输入设备pid');
      		return;
    		}
    		sendBind($scope.pid);
		} else {
			$.actions({
				title: "确定解除绑定？",
  				actions: [{
    			text: "确定",
    			className: "color-primary",
    			onClick: function() {
    				sendUnbind();
    			}
  			}]
			});	
		}		
	}
	//联系信息
	$scope.contact = function(){
		$.alert("若长时间等待数据加载中，请重新刷新页面", "注意事项");
	}
	/*//打开/关闭控制模式
	$scope.$watch('isControllModel', function(newValue,oldValue){ 
		if (newValue) {
			//vb_disconnect();
		}else {
			//vb_connect();
		}
	});*/
});