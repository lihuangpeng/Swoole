<?php

namespace library\mswoole\rpc_client;

class Packet
{
    /**
     * 固定包头 + 包体协议数据生成
     * @param $data
     * @return string
     */
    public static function length_encode($data)
    {
        $json_string = json_encode($data);
        $pack_data = pack('N', strlen($json_string)) . $json_string; //pack将字符变成32位4字节的2进制的内容
        return $pack_data;
    }

    /**
     * 固定包头 + 包体协议解析
     * @param $data
     * @return array
     */
    public static function length_decode($data)
    {
        $header = substr($data, 0, 4); //取前4个字节
        $length = unpack('Nlen', $header);
        return json_decode(substr($data, 4, $length['len']), true);
    }

    /**
     * eof协议
     * @param $data
     * @param string $end
     * @return string
     */
    public function eof_encode($data,$end = "\r\n")
    {
        $json_string = json_encode($data);
        return $json_string.$end;
    }

    /**
     * eof协议
     * @param $data
     * @param string $end
     * @return mixed
     */
    public function eof_decode($data,$end = "\r\n")
    {
        return json_decode(rtrim($data,$end),true);
    }
}