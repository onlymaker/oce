<?php
/**
 * Created by IntelliJ IDEA.
 * User: jibo
 * Date: 2017/2/27
 * Time: 22:00
 */

namespace app\common;

trait Url
{
    function url($target = '')
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' || isset($headers['X-Forwarded-Proto']) && $headers['X-Forwarded-Proto'] == 'https' ? 'https' : 'http';
        $host = $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'] == 80 ? '' : ':' . $_SERVER['SERVER_PORT'];
        $base = rtrim(strtr(dirname($_SERVER['SCRIPT_NAME']), '\\', '/'), '/');
        return $scheme . '://' . $host . $port . $base . $target;
    }
}