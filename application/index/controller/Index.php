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
                case 'news':
                    $result = $this->receiveEvent($postObj);
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
                $content = "欢迎关注【茅丝录】\n微信公众号：gmm_Ice\n请回复：hello";
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
        }elseif ($keyword == 'graphic'){
            $title1 = '我的CSDN';
            $description1 = '我是美女';
            $picurl1 = $_SERVER['HTTP_HOST'].'/weChat/public/static/image/big_spring.jpeg';
            $url1 = 'http://blog.csdn.net/maosilu_ICE';
            $title2 = '我的开源中国';
            $description2 = '我是才女';
            $picurl2 = $_SERVER['HTTP_HOST'].'/weChat/public/static/image/small_spring.jpeg';
            $url2 = 'https://my.oschina.net/maosilu/blog';
            $template = '<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[news]]></MsgType>
        <ArticleCount>2</ArticleCount>
        <Articles>
            <item>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
            </item>
            <item>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
            </item>
        </Articles>
        </xml>';
            $info = sprintf($template, $toUser, $fromUser, time(), $title1, $description1, $picurl1, $url1, $title2, $description2, $picurl2, $url2);
            return $info;
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
    //接收图文请求，回复单图文
    private function receiveNews($postObj){
        $keyword = trim($postObj->Content);
        if(strtolower($keyword) == 'graphic'){
            $title1 = '我的CSDN';
            $description1 = '我是美女';
            $picurl1 = $_SERVER['HTTP_HOST'].'/weChat/public/static/image/big_spring.jpeg';
            $url1 = 'http://blog.csdn.net/maosilu_ICE';
            $title2 = '我的开源中国';
            $description2 = '我是才女';
            $picurl2 = $_SERVER['HTTP_HOST'].'/weChat/public/static/image/small_spring.jpeg';
            $url2 = 'https://my.oschina.net/maosilu/blog';
        }

        $template = '<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[news]]></MsgType>
        <ArticleCount>2</ArticleCount>
        <Articles>
            <item>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
            </item>
            <item>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
            </item>
        </Articles>
        </xml>';
        $toUser = $postObj->FromUserName;
        $fromUser = $postObj->ToUserName;

        $info = sprintf($template, $toUser, $fromUser, time(), $title1, $description1, $picurl1, $url1, $title2, $description2, $picurl2, $url2);
        return $info;

    }

    //test
    public function show(){

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
