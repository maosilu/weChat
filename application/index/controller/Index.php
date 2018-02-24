<?php
namespace app\index\controller;

class Index
{
    public function index()
    {
        echo 'hahaha';
        die;
        /*
         * 接入微信开发API
         * */
        //1. 将nonce，timestamp，token按字典顺序排序
        $nonce = $_GET['nonce'];
        $timestamp = $_GET['timestamp'];
        $token = 'weChat';
        $signature = $_GET['signature'];
        $arr = array($nonce, $timestamp, $token);
        sort($arr);
        //2. 将排序后的三个参数拼接之后用sha1()加密
        $tempStr = implode('', $arr);
        $tempStr = sha1($tempStr);
        //3. 将加密后的字符串与signature进行对比，判断该请求是否来自微信
        if($tempStr == $signature){
            echo $_GET['echostr'];
            exit;

        }
    }

    public function show(){
        echo 'Hello world';
    }
}
