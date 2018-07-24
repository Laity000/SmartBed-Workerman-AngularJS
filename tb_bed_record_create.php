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
$sql = "CREATE TABLE tb_bed_record( ".
        "pid CHAR(8) NOT NULL, ".
        "password VARCHAR(32), ".
        "current_head TINYINT,".
        "current_leg TINYINT,".
        "current_left TINYINT,".
        "current_right TINYINT,".
        "current_lift TINYINT,".
        "current_before TINYINT,".
        "current_after TINYINT,".
        "time DATETIME,".
        "PRIMARY KEY ( pid ))ENGINE=InnoDB DEFAULT CHARSET=utf8; ";
mysqli_select_db( $conn, 'db_smartbed' );
$retval = mysqli_query( $conn, $sql );
if(! $retval )
{
    die('create fail: ' . mysqli_error($conn));
}
echo "create success!\n";
mysqli_close($conn);
?>