基于Workerman的物联网后端管理平台设计
===

搭建物联网后端，实现对**设备的管理**(这里的设备是护理床，主要可以实现对姿态的实时记录、控制)，具体功能如下。

# 功能特征：
 - 设备是基于**TCP长连接**，指令/反馈不定期实时推送，因此需要长连接双向通信。
 - 设备需要**后端平台的管理**支撑，如设备的区分，数据长久化保存，设备批量化管理
 - 设备需要**一对多的绑定**，实现多用户不同终端的实时监控
 - 用户端需要查询设备的实时状态（观察者权限）、授权控制设备（控制者权限）

# 后端关键技术:
 - 使用Workerman中GatewayWorker框架（类似IM场景）
 - 长连接、有状态、双向通信，实现指令和反馈的不定期实时推送
 - 双协议：TCP+自定义数据包格式（设备端），WebSocket+Json格式（用户端）
 - 发布订阅模式，实现设备一对多用户。也可以实现点对点应答模式
 - 业务逻辑清晰：设备登录，用户绑定，实时反馈（观察者权限），授权控制（控制者权限）
 - 设备状态实时记录到MySQL数据库中，支持按日期查询

# 前端关键技术：
 - AngularJS
 - jQuery WEUI
 
# 我是图片的搬运工

 - 通过二维码绑定设备：

  ![](/screenshots/bind.gif "bind")

 - 控制设备并实时反馈：

  ![](/screenshots/control.gif "control")
 - 查询设备历史数据：

  ![](/screenshots/record.gif "record")

# 快速开始

 - 在线测试地址(绑定虚拟机设备):

 ![](/screenshots/admin1.png "admin1")

 - 本地测试：首先，运行start_for_win.bat；本地地址：127.0.0.1:55151

 >注意，本地运行需要配置运行环境：
 > - 需要php的支持(([这里](http://doc.workerman.net/install/install.html))))
 > - 需要mysql数据库的支持(mysql安装教程：([这里](http://www.runoob.com/mysql/mysql-install.html)))，新建db_smartbed数据库后，分别创建bed表和posture表(创建脚本在mysql_create文件夹中，需要在Event.php中修改数据库配置)。
 
# Reference：
 - 我的设计思路：https://blog.csdn.net/u013480581/article/details/80931778
 - workerman：https://www.workerman.net/