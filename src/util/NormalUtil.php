<?php
namespace mia\miagroup\Util;

/**
 * 常用工具类
 *
 * @author user
 */
class NormalUtil {

    /**
     * 宝宝生日转换成n年n月n天
     */
    public static function birth_day_change($birth_day, $compare_day = '') {
        $days = '';
        if ($compare_day == '') {
            $compare_day = date("Y-m-d");
        }
        if ($birth_day >= $compare_day) {
            $days = "待产";
        } elseif ($birth_day == '0000-00-00') {
            $days = "未设置";
        } else {
            $datetime1 = date_create($birth_day);
            $datetime2 = date_create($compare_day);
            $interval = date_diff($datetime1, $datetime2);
            $day_str = $interval->format('%y,%m,%d');
            $day_arr = explode(",", $day_str);
            if ($day_arr[0] > 0) {
                $days = $day_arr[0] . "岁";
            }
            if ($day_arr[1] != 0) {
                $days .= $day_arr[1] . "个月";
            }
            if ($day_arr[2] != 0 && $day_arr[0] < 1) {
                $days .= $day_arr[2] . "天";
            }
        }
        return $days;
    }

    /**
     * 宝宝生日转换成n年n月n天
     */
    public static function getAgeByBirthday($birth_day) {
        if (!$birth_day || $birth_day == '0000-00-00') {
            return false;
        }
        $dateInfo = array();
        $compare_day = date("Y-m-d", time());
        if ($birth_day >= $compare_day) {
            $pregnantTimeLine = strtotime($birth_day) - 86400 * 280;
            if ($pregnantTimeLine > strtotime($compare_day)) {
                return false;
            }
            $chaTimes = strtotime($compare_day) - $pregnantTimeLine;
            $chaDays = round($chaTimes / 86400);
            $weeks = floor($chaDays / 7);
            $days = round($chaDays % 7);
            $dateInfo = array("type" => "pregnant", "week" => $weeks, "day" => $days);
        } else {
            $datetime1 = date_create($birth_day);
            $datetime2 = date_create($compare_day);
            $interval = date_diff($datetime1, $datetime2);
            $day_str = $interval->format('%y,%m,%d');
            $day_arr = explode(",", $day_str);
            $dateInfo = array("type" => "age", "year" => $day_arr[0], "month" => $day_arr[1], "day" => $day_arr[2]);
        }
        return $dateInfo;
    }
    
    
    /**
     * 加密UID
     * @param int $uid
     * @return str
     */
    public static function encode_uid($uid) {
        $newcookie = array();
        $cookie = base64_encode($uid);
        for($i = 0; $i <= strlen($cookie); $i ++) {
            $newcookie[$i] = ord($cookie[$i]);
        }
        $newcookie = implode('.', $newcookie);
        return base64_encode($newcookie . substr($newcookie, 0, 2));
    }
    
    /**
     * 解密UID
     * @param str $val  加密后的UID
     * @return str
     */
    public static function decode_uid($val) {
        $oldcookie = substr(base64_decode($val), 0, -2);
        $newcookie = array();
        $cookie = explode('.', $oldcookie);
        for($i = 0; $i <= strlen($oldcookie); $i ++) {
            $newcookie[$i] = chr($cookie[$i]);
        }
        $newcookie = implode('', $newcookie);
        $newcookie = base64_decode($newcookie);
        return $newcookie;
    }
    
    /**
     * 构建选题分享信息
     */
    public static function buildGroupShare($shareStruct, $replace) {
        //闭包函数,将一个字符串中的所有可替代字符，全部替代
        $func_replace = function($string, $replace) {
            foreach ($replace as $key => $re) {
                $string = str_replace($key, $re, $string);
            }
            return $string;
        };
        foreach ($shareStruct as $k => $s) {
            $shareStruct[$k] = $func_replace($s, $replace);
        }
        return $shareStruct;
    }
    
    /**
     * 构建消息体
     * {"type":3,"extra":"{\"count\":\"10\\u4e07\"}"}
     */
    public static function getMessageBody($type,$chat_room_id,$user_id=0,$content='',$extra=array()){
        $message = [];
        if(!empty($user_id)){
            $user = new \mia\miagroup\Data\User\User();
            $user_info = $user->getUserInfoByIds($user_id)[0];
            $nick = $user_info['nickname'] ?: $user_info['username'];
        }
        $extra_json = json_encode($extra);
        $serviceExtra = ['chat_room_id'=>$chat_room_id];
        
        switch ($type){
            case 0: //系统消息
                $message=['type'=>$type,'user'=>['id'=>-1,'name'=>'蜜芽提醒'],'content'=>$content,'service_extra'=>$serviceExtra];
                break;
            case 1: //观众加入直播
            case 2: //用户发布评论
            case 4: //分享直播
            case 8: //关注主播
                $message=['type'=>$type,'user'=>['id'=>$user_id,'name'=>$nick],'content'=>$content,'service_extra'=>$serviceExtra];
                break;
            case 3: //点赞
                $message=['type'=>$type,'user'=>['id'=>$user_id,'name'=>$nick],'extra'=>$extra_json,'service_extra'=>$serviceExtra];
                break;
            case 5: //在线人数改变
            case 6: //已售卖人数改变
            case 7: //观众显示抢红包按钮
            case 11: //主播显示发红包按钮
            case 12: //直播展位改变
            case 13: //观众显示抢优惠券按钮
                $message=['type'=>$type,'extra'=>$extra_json,'service_extra'=>$serviceExtra];
                break;
            case 9: //直播结束
                $message=['type'=>$type,'service_extra'=>$serviceExtra];
                break;
            case 10: //主播直播过程中状态改变
                $message=['type'=>$type,'user'=>['id'=>$user_id,'name'=>$nick],'content'=>$content,'extra'=>$extra_json,'service_extra'=>$serviceExtra];
                break;
        }
        
        return json_encode($message);
    }
    
    /**
     * 获取配置文件
     * @param unknown $opt
     */
    public static function getConfig($opt){
        $data = \F_Ice::$ins->workApp->config->get($opt);
        return $data;
    }
    
    public static function getHashPath($soursId)
    {
        $hashFirPath = floor($soursId / 100000);
        $hashSecPath = floor($soursId / 1000);
        $newSavePath = 'item/' . $hashFirPath . "/" . $hashSecPath . "/";
        return $newSavePath;
    }
    
    /**
     * 图片url生成(不含宽高)
     */
    public static function getImgUrl($img_path, $type) {
        if (preg_match("/^(http|https):\/\//", $img_path)) {
            return $img_path;
        }
        $host = \F_Ice::$ins->workApp->config->get('busconf.subject.img_watermark_url');
        $img_format = \F_Ice::$ins->workApp->config->get('busconf.subject.img_format');
        if($type == 'original'){
            $host = \F_Ice::$ins->workApp->config->get('app.url.img_url');
            if(substr($host, -1) == '/'){
                $host = substr($host, 0, -1);
            }
        }
        if($img_path[0] != '/'){
            $img_path = '/' . $img_path;
        }
        $pathurl = pathinfo($img_path);
        if ($pathurl['extension'] == 'gif') { //gif格式直接返回原图
            $type = 'original';
        }
        switch ($type){
            case 'original':
                $url = $host . $img_path;
                break;
            default :
                $url = $host . $pathurl['dirname'] . '/' . $pathurl['filename'] . '.' . $pathurl['extension'] . $img_format['subject'][$type]['file_type'];
        }
        return $url;
    }
    
    /**
     * 图片地址生成器（含宽高）
     * @param $url 图片地址
     * @param $type 图片类型：small 小图   watermark 水印图  normal 正常图
     */
    public static function buildImgUrl($url, $type, $width=640, $height=640){
        if (preg_match("/^(http|https):\/\//", $url)) {
            return ['url'=>$url,'width'=>$width,'height'=>$height];
        }
        $host = \F_Ice::$ins->workApp->config->get('busconf.subject.img_watermark_url');
        $img_format = \F_Ice::$ins->workApp->config->get('busconf.subject.img_format');
        if($type == 'original'){
            $host = \F_Ice::$ins->workApp->config->get('app.url.img_url');
            if(substr($host, -1) == '/'){
                $host = substr($host, 0, -1);
            }
        }
        if($url[0] != '/'){
            $url = '/' . $url;
        }
        $pathurl = pathinfo($url);
        if ($pathurl['extension'] == 'gif') { //gif格式直接返回原图
            $type = 'original';
        }
        switch ($type){
            case 'original':
                $url = $host . $url;
                $real_width = $width;
                $real_height = $height;
                break;
            default :
                $url = $host . $pathurl['dirname'] . '/' . $pathurl['filename'] . '.' . $pathurl['extension'] . $img_format['subject'][$type]['file_type'];
                if($img_format['subject'][$type]['limit_width'] && $img_format['subject'][$type]['limit_height']){
                    $real_width = $img_format['subject'][$type]['width'];
                    $real_height = $img_format['subject'][$type]['height'];
                }elseif($img_format['subject'][$type]['limit_width'] && !$img_format['subject'][$type]['limit_height']){
                    $real_width = $img_format['subject'][$type]['width'];
                    $real_height = intval(ceil(($img_format['subject'][$type]['width']/$width) * $height));
                }elseif(!$img_format['subject'][$type]['limit_width'] && $img_format['subject'][$type]['limit_height']){
                    $real_width = intval(ceil(($img_format['subject'][$type]['height']/$height) * $width));
                    $real_height = $img_format['subject'][$type]['height'];
                }else{
                    $real_width = $width;
                    $real_height = $height;
                }
        }
        return ['url'=>$url,'width'=>$real_width,'height'=>$real_height];
    }

    /**
     * curl post 第三方服务请求方法，并记录日志
     * @param $third_server
     * @param $url
     * @param $params
     * @param array $headers
     * @return mixed|null|string
     */
    public static function curlPost($third_server, $url, $params, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($headers) {
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        } else {
            curl_setopt($ch, CURLOPT_HEADER, false);
        }
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        $result = curl_exec($ch);
        $error_no = curl_errno($ch);
        $error_str = curl_error($ch);
        $getCurlInfo = curl_getinfo($ch);

        $result = json_decode($result, true);
        curl_close($ch);

        //记录日志
        \F_Ice::$ins->mainApp->logger_remote->info(array(
            'third_server' => $third_server,
            'type' => 'INFO',
            'request_url' => $url,
            'request_params' => $params,
            'response_code' => $error_no,
            'response_msg' => $error_str,
            'resp_time' => $getCurlInfo['total_time'],
        ));
        return $result;
    }

    /**
     * 以get方式提交到对应的接口url
     * @param string $url
     * @param int $second   url执行超时时间，默认30s
     * @throws WxPayException
     * @return array
     */
    public static function getCurl($third_server, $url, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);


        //运行curl
        $result = curl_exec($ch);
        $error_no = curl_errno($ch);
        $error_str = curl_error($ch);
        $getCurlInfo = curl_getinfo($ch);

        $result = json_decode($result, true);
        //记录日志
        \F_Ice::$ins->mainApp->logger_remote->info(array(
            'third_server' => $third_server,
            'type' => 'INFO',
            'request_url' => $url,
            'get_url' => $url,
            'response_code' => $error_no,
            'response_msg' => $error_str,
            'resp_time' => $getCurlInfo['total_time'],
        ));

        //返回结果
        return $result;
    }


    /**
     * 把超过4位数的数字转换为几点几万
     */
    public static function formatNum($num)
    {
        if ($num <= 9999) {
            return $num;
        }
        $num = number_format($num / 10000, 1, ".", "")."万";
        return $num;
    }

    /*
     * 把缺少/的Unicode加上/
     */
    function unicodeStr($str)
    {
        $cha = mb_detect_encoding($str);
        if ($cha == 'utf-8') {
            return $str;
        }
        preg_match_all("/\\\\?[uU].{4}/", $str, $match);
        foreach ($match[0] as $val) {
            $replace = "\\".trim($val,"\\");
            $str = str_replace($val,$replace,$str);
        }
        return json_decode('["'.$str.'"]',true)[0];
    }


    /**
     * 格式化消息时间，根据当前时间
     * 1. 当天发的消息显示“时:分”，时按24小时制，如“23:59”；
     * 2. 昨日（T-1）发的消息显示“昨日  时:分”，如“昨日  23:59”；
     * 3. 一个自然周内发的消息且超过昨日的时间则显示“星期几  时:分”，如“星期一  23:59”；
     * 4. 超过一个自然周发的消息显示“年-月-日  时:分”，如“2017-07-10  23:59”
     *
     * @param $timeStr string 时间 "2017-08-16 11:33:27"
     * @return string
     */
    public function formatNewsDate($timeStr)
    {
        $unixTime = strtotime($timeStr);
        //当天0点时间
        $today = strtotime(date('Y-m-d',time()));
        //检查是否是当天
        if($unixTime >= $today) {
            return date("H:i",$unixTime);
        }
        //当周
        $monday = strtotime("last Monday");
        if($unixTime > $monday) {
            $w = date("N",$unixTime);
            switch ($w) {
                case 1:
                    $return_time = "星期一";
                    break;
                case 2:
                    $return_time = "星期二";
                    break;
                case 3:
                    $return_time = "星期三";
                    break;
                case 4:
                    $return_time = "星期四";
                    break;
                case 5:
                    $return_time = "星期五";
                    break;
                case 6:
                    $return_time = "星期六";
                    break;
                case 7:
                    $return_time = "星期日";
                    break;
                default:
                    $return_time = "未知";
                    break;
            }
            return $return_time;
        }
        return date("Y-m-d",$unixTime);
    }

}
