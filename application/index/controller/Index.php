<?php
namespace app\index\controller;

class Index
{
    public function index()
    {
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

    /**
     * 接收事件订阅与回复响应消息
     * */
    public function responseMsg(){
        //1. 获取到微信推送过来的post数据（xml格式）
        $postArr = $GLOBALS['HTTP_RAW_POST_DATA'];
        //2. 处理消息类型，并设置回复类型和内容
        /*<xml>
        <ToUserName>< ![CDATA[toUser] ]></ToUserName>
        <FromUserName>< ![CDATA[FromUser] ]></FromUserName>
        <CreateTime>123456789</CreateTime>
        <MsgType>< ![CDATA[event] ]></MsgType>
        <Event>< ![CDATA[subscribe] ]></Event>
        </xml>*/
        $postObj = simplexml_load_string($postArr);
        //判断该数据包是否是subscribe的事件推送
        if(strtolower($postObj->MsgType) == 'event'){
            //如果是subscribe事件
            if(strtolower($postObj->Event) == 'subscribe'){
                //回复用户消息（text类型）
                /*<xml>
                <ToUserName>< ![CDATA[toUser] ]></ToUserName>
                <FromUserName>< ![CDATA[fromUser] ]></FromUserName>
                <CreateTime>12345678</CreateTime>
                <MsgType>< ![CDATA[text] ]></MsgType>
                <Content>< ![CDATA[你好] ]></Content>
                </xml>*/
                $toUser = $postObj->FromUserName;
                $fromUser = $postObj->ToUserName;
                $time = time();
                $msgType = 'text';
                $content = '欢迎关注我们的微信公众号。';
                $template = "<xml>
                <ToUserName>< ![CDATA[%s] ]></ToUserName>
                <FromUserName>< ![CDATA[%s] ]></FromUserName>
                <CreateTime>%d</CreateTime>
                <MsgType>< ![CDATA[%s] ]></MsgType>
                <Content>< ![CDATA[%s] ]></Content>
                </xml>";
                $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
                echo $info;
            }
        }
    }

    //test
    public function show(){
        echo 'Hello world';
    }
}