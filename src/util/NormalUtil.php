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
     * @return
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
     * @return
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
    public static function getMessageBody($type,$user_id=0,$content='',$extra=array()){
        $message = [];
        if(!empty($user_id)){
            $user = new \mia\miagroup\Data\User\User();
            $user_info = $user->getUserInfoByIds($user_id)[0];
            $nick = $user_info['nickname'] ?: $user_info['username'];
        }
        $extra_json = json_encode($extra);

        switch ($type){
            case 0:
                $message=['type'=>$type,'user'=>['id'=>-1,'name'=>'蜜芽提醒'],'content'=>$content];
                break;
            case 1:
            case 2:
            case 4:
            case 8:
                $message=['type'=>$type,'user'=>['id'=>$user_id,'name'=>$nick],'content'=>$content];
                break;
            case 3:
                $message=['type'=>$type,'user'=>['id'=>$user_id,'name'=>$nick],'extra'=>$extra_json];
                break;
            case 5:
            case 6:
            case 7:
            case 11:
            case 12:
                $message=['type'=>$type,'extra'=>$extra_json];
                break;
            case 9:
                $message=['type'=>$type];
                break;
            case 10:
                $message=['type'=>$type,'user'=>['id'=>$user_id,'name'=>$nick],'content'=>$content,'extra'=>$extra_json];
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
    
}
