<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/*
 * url解析
 * @param   $url        string     url
 * @param   $type       string   请求类型
 * @param   $post_data  json       post请求参数
 * @param   $resType    string   返回数据类型
 * @return  $res        mixed      url请求解析结果
 * */
function http_curl($url, $type='get', $post_data = '', $resType = 'array'){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($type == 'post'){
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURL_POSTFIELDS, $post_data);
    }
    $res = curl_exec($ch);
    if(curl_errno($ch)){
        echo 'curl error: '.curl_error($ch)."\n";
    }
    curl_close($ch);

    if($resType == 'array'){
        return json_decode($res, true);
    }
}

/*
 * 发送json字符串信息
 * @param $res  mixed  要解析的参数
 * @return null
 * */
function sendJson($res){
    if(is_array($res)){
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }else{
        echo $res;
    }
}
