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
    public function responseGraphic($postObj, $graphic_arr){
        $toUser = $postObj->FromUserName;
        $fromUser = $postObj->ToUserName;
        $template = "<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[news]]></MsgType>
        <ArticleCount>".count($graphic_arr)."</ArticleCount>
        <Articles>";
        foreach($graphic_arr as $v){
            $template .= "<item>
                <Title><![CDATA[".$v['title']."]]></Title>
                <Description><![CDATA[".$v['description']."]]></Description>
                <PicUrl><![CDATA[".$v['picUrl']."]]></PicUrl>
                <Url><![CDATA[".$v['url']."]]></Url>
                </item>";
        }
        $template .= '</Articles></xml>';
//        $info = sprintf($template, $toUser, $fromUser, time(), $title1, $description1, $picurl1, $url1, $title2, $description2, $picurl2, $url2);
        $info = sprintf($template, $toUser, $fromUser, time());
        return $info;
    }
}