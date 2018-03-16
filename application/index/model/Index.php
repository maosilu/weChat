<?php
namespace app\index\model;

use think\Model;

class Index extends Model
{
    //回复文本消息
    public function responseText($postObj, $content = ''){
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

    //回复图文消息
    public function responseGraphic($postObj){
        $toUser = $postObj->FromUserName;
        $fromUser = $postObj->ToUserName;
        $title1 = '我的CSDN';
        $description1 = '我是美女';
        $picurl1 = 'http://'.$_SERVER['HTTP_HOST'].'/weChat/public/static/image/big_spring.jpeg';
        $url1 = 'http://blog.csdn.net/maosilu_ICE';
        $title2 = '我的开源中国';
        $description2 = '我是才女';
        $picurl2 = 'http://'.$_SERVER['HTTP_HOST'].'/weChat/public/static/image/small_spring.jpg';
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
    }
}