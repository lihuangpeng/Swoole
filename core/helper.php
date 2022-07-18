<?php

function hash_func($string, $hash_code = 31, $remainder = 0)
{
    $len = strlen($string);
    $sum = 0;
    for ($i = 0; $i < $len; $i++) {
        $sum += ord($string[$i]) * $hash_code;
    }
    if ($remainder > 0) $sum = $sum % $remainder;
    return $sum;
}

function check_sign($data, $secret, $sign_key = 'sign')
{
    if (!isset($data[$sign_key])) return false;
    $check_sign = $data[$sign_key];
    unset($data[$sign_key]);
    $str = '';
    foreach ($data as $key => $value) {
        if (!is_string($value)) {
            $str .= serialize($value);
        } else {
            $str .= $value;
        }
    }
    $sign = md5($str . $secret);
    return $sign === $check_sign;
}

function get_client_ip($server, $type = 0)
{
    $type = $type ? 1 : 0;
    $ip = NULL;
    if ($ip !== NULL) return $ip[$type];
    if (isset($server['HTTP_X_REAL_IP'])) {//nginx 代理模式下，获取客户端真实IP
        $ip = $server['HTTP_X_REAL_IP'];
    } elseif (isset($server['HTTP_CLIENT_IP'])) {//客户端的ip
        $ip = $server['HTTP_CLIENT_IP'];
    } elseif (isset($server['HTTP_X_FORWARDED_FOR'])) {//多级代理
        $arr = explode(',', $server['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown', $arr);
        if (false !== $pos) unset($arr[$pos]);
        $ip = trim($arr[0]);
    } elseif (isset($server['REMOTE_ADDR'])) {
        $ip = $server['REMOTE_ADDR'];//浏览当前页面的用户计算机的ip地址
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

function dump($value)
{
    Core::dump($value);
}
