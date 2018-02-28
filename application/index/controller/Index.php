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
        sort($arr, SORT_STRING);
        //2. 将排序后的三个参数拼接之后用sha1()加密
        $tempStr = implode('', $arr);
        $tempStr = sha1($tempStr);
        //3. 将加密后的字符串与signature进行对比，判断该请求是否来自微信
        //第一次接入微信API的时候，微信验证第三方url的有效性会发送echostr这个参数
        $echoStr = $_GET['echostr'];
        if($tempStr == $signature && isset($echoStr)){
            echo $echoStr;
            exit;
        }else{
            $this->responseMsg();
        }
    }

    /**
     * 接收事件与回复响应消息
     * */
    public function responseMsg(){
        //1. 获取到微信推送过来的post数据（xml格式）
        $postXml = $GLOBALS["HTTP_RAW_POST_DATA"];
        file_put_contents('response.txt', 'hhhh'.$postXml, FILE_APPEND);
        //2. 处理消息类型，并设置回复类型和内容
        /*<xml>
        <ToUserName>< ![CDATA[toUser] ]></ToUserName>
        <FromUserName>< ![CDATA[FromUser] ]></FromUserName>
        <CreateTime>123456789</CreateTime>
        <MsgType>< ![CDATA[event] ]></MsgType>
        <Event>< ![CDATA[subscribe] ]></Event>
        </xml>*/
//        $postXml = "<xml><ToUserName><![CDATA[gh_37262390bb3e]]></ToUserName> <FromUserName><![CDATA[o8UM4s6PjgA4hznjsMgfpIvdpWkQ]]></FromUserName> <CreateTime>1519781728</CreateTime> <MsgType><![CDATA[text]]></MsgType> <Content><![CDATA[hello]]></Content> <MsgId>6527412819269196574</MsgId> </xml>";
        $postObj = simplexml_load_string($postXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        //判断该数据包是否是subscribe的事件推送
        if(strtolower($postObj->MsgType) == 'event'){
            //如果是subscribe事件
            if(strtolower($postObj->Event) == 'subscribe'){
                //回复用户消息（text类型）
                /*<xml>
                <ToUserName><![CDATA[toUser]]></ToUserName>
                <FromUserName><![CDATA[fromUser]]></FromUserName>
                <CreateTime>12345678</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA[你好]]></Content>
                </xml>*/
                $toUser = $postObj->FromUserName;
                $fromUser = $postObj->ToUserName;
                $content = "欢迎关注【茅丝录】\n微信公众号：$toUser";
                $template = '<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA[%s]]></Content>
                </xml>';
                $info = sprintf($template, $toUser, $fromUser, time(), $content);
                var_dump($info);
            }
        }

        //接收文本消息并回复纯文本消息
        /*<xml>
        <ToUserName>< ![CDATA[toUser] ]></ToUserName>
        <FromUserName>< ![CDATA[fromUser] ]></FromUserName>
        <CreateTime>1348831860</CreateTime>
        <MsgType>< ![CDATA[text] ]></MsgType>
        <Content>< ![CDATA[this is a test] ]></Content>
        <MsgId>1234567890123456</MsgId>
        </xml>*/
        if(strtolower($postObj->MsgType) == 'text'){
            if(strtolower($postObj->Content) == 'hello'){
                $template = '<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>';
                $toUser = $postObj->FromUserName;
                $fromUser = $postObj->ToUserName;
                $content = 'hello world';
                $info = sprintf($template, $toUser, $fromUser, time(), $content);
                var_dump($info);
            }
        }
    }

    //test
    public function show(){
        $xml = "<xml>
        <ToUserName><![CDATA[toUser]]></ToUserName>
        <FromUserName><![CDATA[fromUser]]></FromUserName>
        <CreateTime>123456789</CreateTime>
        <MsgType><![CDATA[event]]></MsgType>
        <Event><![CDATA[subscribe]]></Event>
        </xml>";
        $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        var_dump( $obj->ToUserName);
        var_dump($obj);
    }
}
