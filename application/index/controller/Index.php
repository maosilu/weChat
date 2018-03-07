<?php
namespace app\index\controller;

use app\index\model;

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
        if($tempStr == $signature && isset($_GET['echostr'])){
            echo $_GET['echostr'];
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
        //因为很多都设置了register_globals禁止，不能用 $GLOBALS['HTTP_RAW_POST_DATA']
//        $postXml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $postXml = file_get_contents("php://input");
        if(!empty($postXml)){
            file_put_contents('response.txt', '微信推送过来的信息：'.$postXml.'\r\n', FILE_APPEND);
            //2. 处理消息类型，并设置回复类型和内容
            $postObj = simplexml_load_string($postXml, 'SimpleXMLElement', LIBXML_NOCDATA);
            $msgType = strtolower($postObj->MsgType);
            switch($msgType){
                case 'event':
                    $result = $this->receiveEvent($postObj);
                    break;
                case 'text':
                    $result = $this->receiveText($postObj);
                    break;
                default:
                    $result = 'Unknow msg type: '.$msgType;
                    break;
            }
            file_put_contents('response.txt', '回复用户的信息：'.$postXml.'\r\n', FILE_APPEND);
            echo $result;
        }else{
            echo '';
            exit;
        }

    }

    //接收事件消息，回复文本消息
    private function receiveEvent($postObj){
        //接收的事件
        /*<xml>
            <ToUserName>< ![CDATA[toUser] ]></ToUserName>
            <FromUserName>< ![CDATA[FromUser] ]></FromUserName>
            <CreateTime>123456789</CreateTime>
            <MsgType>< ![CDATA[event] ]></MsgType>
            <Event>< ![CDATA[subscribe] ]></Event>
            </xml>*/
        $event = strtolower($postObj->Event);
        switch ($event){
            case 'subscribe':
                //回复用户消息（text类型）
                $content = "欢迎关注【茅丝录】\n微信公众号：gmm_Ice\n请回复：\n(1): hello\n(2): 图文\n(3): 除以上的任意其他文字有惊喜哦～";
                break;
            case 'unsubscribe':
                $content = '您已取消关注，我会想你的～';
                break;
            default:
                $content = 'receive a new event:'.$postObj->Event;
                break;
        }
        $toUser = $postObj->FromUserName;
        $fromUser = $postObj->ToUserName;
        $template = '<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA[%s]]></Content>
                </xml>';
        $info = sprintf($template, $toUser, $fromUser, time(), $content);
        return $info;
    }
    //接收文本消息，回复文本消息
    private function receiveText($postObj){
        $toUser = $postObj->FromUserName;
        $fromUser = $postObj->ToUserName;
        //接收的文本消息
        /*<xml>
            <ToUserName>< ![CDATA[toUser] ]></ToUserName>
            <FromUserName>< ![CDATA[fromUser] ]></FromUserName>
            <CreateTime>1348831860</CreateTime>
            <MsgType>< ![CDATA[text] ]></MsgType>
            <Content>< ![CDATA[this is a test] ]></Content>
            <MsgId>1234567890123456</MsgId>
            </xml>*/
        $keyword = strtolower(trim($postObj->Content));
        if($keyword == 'hello'){
            $content = 'hello world';
        }elseif ($keyword == '图文'){
//            (new \app\index\model\Index())->responseGraphic();
            return (new model\Index)->responseGraphic($postObj);

        }else{
            $content = date('Y-m-d H:i:s', time())."\r\n".'<a href="https://github.com/maosilu/weChat">代码git地址</a>'."\r\n嘣嘣嘣嘣！～";
        }

        $template = '<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>';
        $info = sprintf($template, $toUser, $fromUser, time(), $content);
        return $info;

    }

    // 获取access_token
    public function getAccessToken(){
        $appid = 'wx6c8e0aca5b997b12';
        $secret = 'd534279af4b3481c07ffece9454e3e06';
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$secret;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        if(curl_errno($ch)){
            echo 'curl error: '.curl_error($ch)."\n";
        }
        curl_close($ch);
//        var_dump(json_decode($res, true));
        return json_decode($res, true);
    }

    //获取微信服务器IP地址
    public function getWxServerIp(){
        //http请求方式: GET https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=ACCESS_TOKEN
        $res = $this->getAccessToken();
        if(!isset($res['access_token'])){
            echo 'access_token获取失败！';
            exit;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token='.$res['access_token'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        if(curl_errno($ch)){
            echo 'curl error: '.curl_error($ch)."\n";
        }
        curl_close($ch);
        var_dump(json_decode($res, true));
    }

    //test
    public function show(){
        $picurl1 = $_SERVER['HTTP_HOST'].'/weChat/public/static/image/big_spring.jpeg';
//        http://localhost:8080/weChat/public/static/image/big_spring.jpeg
        echo $picurl1;

        var_dump($_SERVER);

        /*$xml = "<xml>
        <ToUserName><![CDATA[toUser]]></ToUserName>
        <FromUserName><![CDATA[fromUser]]></FromUserName>
        <CreateTime>123456789</CreateTime>
        <MsgType><![CDATA[event]]></MsgType>
        <Event><![CDATA[subscribe]]></Event>
        </xml>";
        $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        var_dump( $obj->ToUserName);
        var_dump($obj);*/
    }
}
