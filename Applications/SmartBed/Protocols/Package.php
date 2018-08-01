<?php

namespace Protocols;

require_once __DIR__ . '/../Utils.php';

class Package
{
    public static function input($buffer){
        //长度小于2，继续等待
        if (strlen($buffer) < 2) {
            return 0;
        }
        //解包
        $unpack_data = unpack('Cfrom/Clength', $buffer);
        
        if ($unpack_data['from'] != 0x5d) {
            return false;
        }
        //返回包长
        return $unpack_data['length'] + 3;
    }


    /**
     * 解码
     * @param string $buffer
     *
     * @return array 
     */
    public static function decode($buffer){
        //echo "message received: ". bin2hex($buffer) ."\n";
        //解包
        $unpack_data = unpack('Clength/Ctype', substr($buffer, 1, 2));
        $data = '';
        switch ($unpack_data['type']) {
            case 0x00:
                //PING 长度为1字节的反馈
                $data =  unpack('Ctype/Csum', substr($buffer, 2));
                break;
            case 0x01:
                //CONNECT 长度为7字节的反馈
                $data =  unpack('Ctype/a6PID/Csum', substr($buffer, 2));
                break;
            case 0x02:
                //DISCONNECT 长度为1字节的反馈
                $data =  unpack('Ctype/Csum', substr($buffer, 2));
                break;
            case 0x03:
                //POSTURE 长度为8字节的反馈
                $data =  unpack('Ctype/Chead/Cleg/Cleft/Cright/Clift/Cbefore/Cafter/Csum', substr($buffer, 2));
                break;
            case 0x04:
                //DONE 长度为8字节的反馈
                $data =  unpack('Ctype/Chead/Cleg/Cleft/Cright/Clift/Cbefore/Cafter/Csum', substr($buffer, 2));
                break;
            case 0x05:
                //UNDONE 长度为1字节的反馈
                $data =  unpack('Ctype/Ctag/Csum', substr($buffer, 2));
                break;
        }
        return $data;
    }

    /**
     * 编码
     * @param array $order
     *
     * @return string 
     */
    public static function encode($order){
        $content_length = $order['length'] - 1;
        if ($content_length == 0) {
            //包校验
            $sum = $order['length'] + $order['type'];
            //打包
            $buffer = pack('CCCC', 0x5d, $order['length'], $order['type'], $sum);
        }else{
            switch ($order['type']) {
            //控制姿态消息
            case 0x13:
                $sum = $order['length'] + $order['type'] + $order['pos'] + $order['angle'];
                $buffer = pack('CCCCCC', 0x5d, $order['length'], $order['type'], $order['pos'], $order['angle'], $sum);
                break;
            }
        }
        //echo "message sended: ". bin2hex($buffer) ."\n";
        return $buffer;
    }
}