<?php
namespace app\index\controller;

use think\Controller;
use app\index\model;
//use app\index\controller\Weather;
use think\Session;
use think\View;

class Index extends Controller
{

    private $appid = '';
    private $secret = '';
    private $appkey = ''; // 申请的聚合天气预报APPKEY
    private $ip = ''; // 你当前访问的域名，也可以是ip，例：192.168.101.94
    private $openid = '';

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
                $content = "欢迎关注【茅丝录】\n微信公众号：gmm_Ice\n请回复：\n(1): hello\n(2): 图文\n(3)：北京\n(4): 除以上的任意其他文字有惊喜哦～";
                break;
            case 'scan': // 扫描带参数的二维码--用户已关注时的事件推送
                if(strtolower($postObj->EventKey) == 2000){ // 临时二维码
                    $tmp = '临时';
                }
                if(strtolower($postObj->EventKey) == 'test'){ // 永久二维码
                    $tmp = '永久';
                }
                // 回复用户图文消息
                $graphic_arr = array(
                    array(
                        'title' => '一个丁香一样的，结着愁怨的姑娘----'.$tmp,
                        'description' => '雨巷',
                        'picUrl' => 'http://'.$_SERVER['HTTP_HOST'].'/weChat/public/static/image/beauty.jpeg',
                        'url' => 'https://www.douban.com/group/topic/3454801/'
                    )
                );
                return (new model\Index)->responseGraphic($postObj, $graphic_arr);
            case 'click': // 自定义菜单中的Event->click
                $eventKey = strtolower($postObj->EventKey);
                switch ($eventKey){
                    case '绘画天地':
                        //回复text消息
                        $content = '这是我的绘画天地';
                        break;
                    case 'songs':
                        $content = '这是我喜欢的歌曲';
                        break;
                }
                break;
            case 'view': // 自定义菜单中的Event->view
                $eventKey = strtolower($postObj->EventKey);
                switch ($eventKey){
                    case 'https://www.baidu.com':
                        //回复text消息
                        $content = "<a href='".$eventKey."'>嗖嗖</a>";
                        break;
                    case 'http://blog.csdn.net/maosilu_ICE':
                        $content = "<a href='".$eventKey."'>我的CSDN</a>";
                        break;
                }
                break;
            case 'unsubscribe':
                $content = '您已取消关注，我会想你的～';
                break;
            default:
                $content = 'receive a new event:'.$postObj->Event;
                break;
        }

        $info = (new model\Index)->responseText($postObj, $content);
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
            $graphic_arr = array(
                array(
                    'title' => '我的CSDN',
                    'description' => '我是美女',
                    'picUrl' => 'http://'.$_SERVER['HTTP_HOST'].'/weChat/public/static/image/big_spring.jpeg',
                    'url' => 'http://blog.csdn.net/maosilu_ICE'
                ),
                array(
                    'title' => '我的开源中国',
                    'description' => '我是才女',
                    'picUrl' => 'http://'.$_SERVER['HTTP_HOST'].'/weChat/public/static/image/small_spring.jpg',
                    'url' => 'https://my.oschina.net/maosilu/blog'
                ),
            );
//            (new \app\index\model\Index())->responseGraphic();
            return (new model\Index)->responseGraphic($postObj, $graphic_arr);
        }elseif($keyword == '北京'){
            $weather = new Weather($this->appkey);
            $cityWeatherResult = $weather->getWeather('北京');
            if($cityWeatherResult['error_code'] == 0){    //以下可根据实际业务需求，自行改写
                $data = $cityWeatherResult['result'];
                $content = "====当前天气实况====\r\n";
                $content .= "温度：".$data['sk']['temp']."    ";
                $content .= "风向：".$data['sk']['wind_direction']."（".$data['sk']['wind_strength']."）";
                $content .= "湿度：".$data['sk']['humidity']."    ";
                $content .= "\r\n\r\n";

                $content .= "==未来几天天气预报==\r\n";
                foreach($data['future'] as $wkey =>$f){
                    $content .= "日期:".$f['date']." ".$f['week']." ".$f['weather']." ".$f['temperature']."\r\n";
                }
                $content .= "\r\n";

                $content .= "====相关天气指数====\r\n";
                $content .= "穿衣指数：".$data['today']['dressing_index']." , ".$data['today']['dressing_advice']."\r\n";
                $content .= "紫外线强度：".$data['today']['uv_index']."\r\n";
                $content .= "舒适指数：".$data['today']['comfort_index']."\r\n";
                $content .= "洗车指数：".$data['today']['wash_index'];
//                $content .= "\n\r\n\r";

            }else{
                 $content = $cityWeatherResult['error_code'].":".$cityWeatherResult['reason'];
            }
        }else{
            $content = date('Y-m-d H:i:s', time())."\r\n".'<a href="https://github.com/maosilu/weChat">代码git地址</a>'."\r\n嘣嘣嘣嘣！～";
        }

        $info = (new model\Index)->responseText($postObj, $content);
        return $info;

    }

    //群发接口
    public function sendMsgAll(){
         $access_token = $this->getAccessToken();
         $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token='.$access_token;
        //发送文本消息
         $post_data = array(
             'touser' => $this->openid,
             'text' => array(
                 'content' => 'I am a beauty.'
             ),
             'msgtype' => 'text'
         );
         //发送图文消息
        /*$post_data = array(
            'touser' => $this->openid,
            'mpnews' => array(
                'media_id' => '123dsdajkasd231jhksad'
            ),
            'msgtype' => 'mpnews'
        );*/
         $res = http_curl($url, 'post', $post_data);
         var_dump($res);
    }

    /**
     * 获取access_token 将access_token存在session/cookie中
    */
    public function getAccessToken(){
//        $access_token = Session::get('access_token');
//        if(!Session::get('access_token')){
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->secret;
            $res = http_curl($url);
            Session::set('access_token', $res['access_token']);
//        }
        return Session::get('access_token');
    }

    //获取微信服务器IP地址
    public function getWxServerIp(){
        //http请求方式: GET https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=ACCESS_TOKEN
        $access_token = $this->getAccessToken();
        if(!isset($access_token)){
            echo 'access_token获取失败！';
            exit;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token='.$access_token;
        $res = http_curl($url);
        var_dump($res);
    }

    //创建微信菜单
    public function definedItem(){
        //http请求方式：POST（请使用https协议） https://api.weixin.qq.com/cgi-bin/menu/create?access_token=ACCESS_TOKEN
        $access_token = $this->getAccessToken();
//        $access_token = '7_hEJC1oDx9Bnb6iXcFDm6ovD66HcuIBVgNib-DDwMV_yFT-WyuJsSR7FKL0rF98JnvxkxgFJbqmneUwQ3Nt75U2TIr_pPss5zJnOEa5OXBXeKIXoEBfb4j7i-shARRWjAEAXXC';
        if(!isset($access_token)){
            echo 'access_token获取失败！';
            exit;
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
        $post_data = array(
            'button' => array( // 一级菜单
                array( // 第一个一级菜单
                    'type' => 'click',
                    'name' => '冰',
                    'key' => '绘画天地',
                ),
                array( // 第二个一级菜单
                    'name' => '美美',
                    'sub_button' => array(
                        array(
                            'type' => 'view',
                            'name' => '搜索',
                            'url' => 'https://www.baidu.com'
                        ),
                        array(
                            'type' => 'click',
                            'name' => '歌曲',
                            'key' => 'songs'
                        ),
                        array(
                            'type' => 'view',
                            'name' => 'My CSDN',
                            'url' => 'http://blog.csdn.net/maosilu_ICE'
                        ),
                    ),
                ),
            ),
        );
        $res = http_curl($url, 'post', $post_data);
        var_dump($res);
    }

    /**
     * 用户同意授权，获取code
     * @param string $function_name 方法名称
     * @param string $scope         授权作用域scope参数
     * @return null
    */
    public function getCode(){
        // 1.获取到code

        $function_name = 'getWebAccessToken';
        // （1）不弹出授权页面，只获取用户openid
        $scope = 'snsapi_base';
        // （2）弹出授权页面，可通过openid拿到用户信息
//        $scope = 'snsapi_userinfo';

        $redirect_uri = urlencode("http://$this->ip/weChat/public/index.php/index/Index/$function_name");
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$this->appid&redirect_uri=$redirect_uri&response_type=code&scope=$scope&state=$scope#wechat_redirect";
        header("Location:".$url); // or redirect($url);
    }
    //获取网页授权的acces_token
    public function getWebAccessToken(){
        // 2.获取到网页授权的access_token
        $code = $_GET['code'];
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$this->appid&secret=$this->secret&code=$code&grant_type=authorization_code";
        $res = http_curl($url);
        if($_GET['state'] == 'snsapi_userinfo'){
            $res = $this->getUser($res);
        }
        var_dump($res);
    }
    // 不弹出授权页面，直接跳转，只能获取用户openid
    public function getUserOpenId($res){
        var_dump($res);
    }
    // 弹出授权页面，可通过openid拿到昵称、性别、所在地。并且， 即使在未关注的情况下，只要用户授权，也能获取其信息
    public function getUser($res){
        // 3.拉取用户信息
        $access_token = $res['access_token'];
        $openid = $res['openid'];
        $user_url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
        $user_res = http_curl($user_url);
        return $user_res;
    }

    //模版消息实现-发送模版消息
    public function sendTemplateMsg(){
        // 1.获取access_token
        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
        // 2.组装数组
        $post_data = array(
            'touser' => $this->openid,
            'template_id' => 'y43l_ICs5XQJu9oKoEHi4F-I8l4S6TUf_R0r5D6h93M',
            'url' => 'https://www.baidu.com',
            'data' => array(
                'first' => array(
                    'value' => "恭喜你购买成功！\r\n",
                    'color' => "#173177"
                ),
                'name' => array(
                    'value' => "巧克力：",
                    'color' => "#173177"
                ),
                'price' => array(
                    'value' => "39.8元\r\n",
                    "color" => "#173177"
                ),
                'date' => array(
                    'value' => date("Y-m-d H:i:s")."\r\n",
                    'color' => "#173177"
                ),
                'remark' => array(
                    'value' => "欢迎再次购买！\r\n",
                    'color' => "#173177"
                )
            )
        );
        // 3.将数组转化为json
        $post_data = json_encode($post_data, JSON_UNESCAPED_UNICODE);
        // 4.调用curl函数
       $res = http_curl($url, 'post', $post_data);
       var_dump($res);
    }

    // 生成带参数的二维码
    public function getQrCode(){
        // 1.获取ticket票据
        //全局票据access_token 网页授权access_token 微信js-SDK jsapi_ticket
        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$access_token;
        // 临时二维码请求
        /*$post_data = array(
            'expire_seconds' => 604800, //有效期7天
            'action_name' => 'QR_SCENE',
            'action_info' => array(
                'scene' => array(
                    'scene_id' => 2000
                )
            )
        );*/
        // 永久二维码请求
        $post_data = array(
            'action_name' => 'QR_LIMIT_STR_SCENE',
            'action_info' => array(
                'scene' => array(
                    'scene_str' => 'test'
                )
            )
        );
        $res = http_curl($url, 'post', $post_data);
//        var_dump($res);die;
        // 2.通过ticket换取二维码图片
        $ticket = urlencode($res['ticket']);
        $qr_url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".$ticket;
        echo "二维码：<br/><img src='".$qr_url."'/>";
        unset($post_data);
    }

    //分享到WeChat
    public function shareWx(){
        $config = get_jssdk($this->appid);
        $this->assign('config', $config);
        return $this->fetch('index/shareWx');
    }

    //test
    public function show(){
        return $this->fetch('show');
//        get_jsapi_ticket();
        /*$user_url = "https://api.weixin.qq.com/sns/userinfo?access_token=7_RDh00xvsXYKPzrm8on6F0fm92KC8lCwpGcABQnkWPDDkzZLDQ2YSHDpbqj-9USUkqZ8mmWXj0l8TbSX_mP_T6b5kmmE5ljiaHqtzf8ys7KE&openid=ohbHRv9UQWbK_5NiGxB_P68fhBoA&lang=zh_CN";
        $user_res = http_curl($user_url);
        var_dump($user_res);*/
//        echo config('session.expire');
        /*$access_token = Session::get('access_token');
        if(isset($access_token)){
            echo "11<br/>";
            echo $access_token;
        }else{
            echo "222<br/>";
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->secret;
            $res = http_curl($url);
            Session::set('access_token', $res['access_token']);
            echo Session::get('access_token');

        }*/


//        $weather = new Weather($this->appkey);
        /*$picurl1 = $_SERVER['HTTP_HOST'].'/weChat/public/static/image/big_spring.jpeg';
        echo $picurl1;

        var_dump($_SERVER);*/

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
