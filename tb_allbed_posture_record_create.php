<?php
$dbhost = 'localhost:3306';  // mysql服务器主机地址
$dbuser = 'root';            // mysql用户名
$dbpass = '123456';          // mysql用户名密码
$conn = mysqli_connect($dbhost, $dbuser, $dbpass);
if(! $conn )
{
    die('connect fail: ' . mysqli_error($conn));
}
echo "connect success!\n";
$sql = "CREATE TABLE tb_posture_record( ".
        "id INT NOT NULL AUTO_INCREMENT, ".
        "pid VARCHAR(32) NOT NULL, ".
        "uid VARCHAR(32), ".
        "posture_head TINYINT,".
        "posture_leg TINYINT,".
        "posture_left TINYINT,".
        "posture_right TINYINT,".
        "posture_lift TINYINT,".
        "time DATETIME, ".
        "PRIMARY KEY ( id ))ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
mysqli_select_db( $conn, 'db_smartbed' );
$retval = mysqli_query( $conn, $sql );
if(! $retval )
{
    die('create fail: ' . mysqli_error($conn));
}
echo "create success!\n";
mysqli_close($conn);
?>