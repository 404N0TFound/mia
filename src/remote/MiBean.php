<?php
namespace mia\miagroup\Remote;

use mia\miagroup\Lib\Thrift;
use mia\miagroup\Lib\Redis;

class MiBean extends Thrift{
    
    protected $service = "MiBean";
    
    /**
     * 增加蜜豆
     * 收到赞+1蜜豆,以天为周期，每天收到N个赞，最多可得3次蜜豆奖励
     * 蜜芽圈发帖+3蜜豆（以天为周期，每天晒N单，最多可得3次蜜豆奖励）
     * 收到别人的评论+1   （以天为周期，每天收到N个别人的评论，最多可得3次蜜豆奖励
     * 精品贴+5   被推荐到首页（以周为周期，被推荐到首页N次，最多可得2次蜜豆奖励）
     * @param unknown $param
     */
    public function add($param){
        $data = [];
        $redis = new Redis();
        //赞
        if($param['relation_type'] == 'receive_praise'){
            //赞 +1蜜豆, （以天为周期，每天收到N个赞，最多可得3次蜜豆奖励）
            $mibean_receive_praise_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.miBeanKey.mibean_give_way.key'),$param['relation_type'],$param['to_user_id']);
            $parise_give_mibean_num = $redis->get($mibean_receive_praise_key);
            if($parise_give_mibean_num < 3){
                //加1蜜豆
                $param['mibean'] = 1;
                $data = $this->agent('add', $param);
                if($data['code'] == 200){
                    $redis->incrBy($mibean_receive_praise_key,1);
                    $redis->expireAt($mibean_receive_praise_key,strtotime(date('Y-m-d 23:59:59')));
                }
            }
        }elseif($param['relation_type'] == 'publish_pic'){
            //  蜜芽圈发帖+3（以天为周期，每天晒N单，最多可得3次蜜豆奖励）
            $mibean_publish_pic_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.miBeanKey.mibean_give_way.key'),$param['relation_type'],$param['user_id']);
            $mibean_publish_pic_num = $redis->get($mibean_publish_pic_key);
            if($mibean_publish_pic_num < 3){
                //加1蜜豆
                $param['mibean'] = 3;
                $data = $this->agent('add', $param);
                if($data['code'] == 200){
                    $redis->incrBy($mibean_publish_pic_key,1);
                    $redis->expireAt($mibean_publish_pic_key,strtotime(date('Y-m-d 23:59:59')));
                }
            }
        }elseif($param['relation_type'] == 'follow_me'){
            //被关注+1    被真实用户关注
            $param['mibean'] = 1;
            $data = $this->agent('add', $param);
        }elseif($param['relation_type'] == 'receive_comment'){
            //收到别人的评论+1   以天为周期，每天收到N个别人的评论，最多可得3次蜜豆奖励，首次关注有效）
            $mibean_receive_comment_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.miBeanKey.mibean_give_way.key'),$param['relation_type'],$param['to_user_id']);
            $mibean_receive_comment_num = $redis->get($mibean_receive_comment_key);
            if($mibean_receive_comment_num < 3){
                //加1蜜豆
                $param['mibean'] = 1;
                $data = $this->agent('add', $param);
                if($data['code'] == 200){
                    $redis->incrBy($mibean_receive_comment_key,1);
                    $redis->expireAt($mibean_receive_comment_key,strtotime(date('Y-m-d 23:59:59')));
                }
            }
        }elseif($param['relation_type'] == 'fine_pic'){
            //  精品贴+5   被推荐到首页（以周为周期，被推荐到首页N次，最多可得2次蜜豆奖励）
            $mibean_fine_pic_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.miBeanKey.mibean_give_way.key'),$param['relation_type'],$param['to_user_id']);
            $mibean_fine_pic_num = $redis->get($mibean_fine_pic_key);
            if($mibean_fine_pic_num < 2){
                //加1蜜豆
                $param['mibean'] = 5;
                $data = $this->agent('add', $param);
                if($data['code'] == 200){
                    $redis->incrBy($mibean_fine_pic_key,1);
                    $w = date('w') == 0 ? 7 : date('w');
                    $fine_pic_time = (7-$w)*86400 + strtotime(date('Y-m-d 23:59:59'));
                    $redis->expireAt($mibean_fine_pic_key,$fine_pic_time);
                }
            }
        }else{
            $data = $this->agent('add', $param);
        }
        return $data;
    }
    
    /**
     * 减少蜜豆
     */
    public function sub($param){
        $data = $this->agent('sub', $param);
        return $data;
    }
    
    /**
     * 检测蜜豆是否送过
     */
    public function check($param){
        $data = $this->agent('check', $param);
        return $data;
    }
    
}