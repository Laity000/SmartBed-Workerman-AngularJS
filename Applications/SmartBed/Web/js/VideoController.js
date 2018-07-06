'use strict';
 //Define `SetupController`

app.controller('VideoController', function($scope, $location) {

    //视频模块
    var player = new EZUIPlayer('myPlayer');
    player.on('error', function(){
        console.log('error');
    });
    player.on('play', function(){
        console.log('play');
    });
    player.on('pause', function(){
        console.log('pause');
    });

    //返回操作
    $scope.return = function(){
   
        player.pause();
        $location.path('home');


    }
            
       
});