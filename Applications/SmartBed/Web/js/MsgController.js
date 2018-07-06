 'use strict';

app.controller('MsgController', ['$scope', '$location',  function($scope, $location) {
	
	$scope.query = function(){
		sendQueryPosture();
	}
	
}]);