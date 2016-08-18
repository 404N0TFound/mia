<?php
namespace mia\miagroup\Remote;

use mia\miagroup\Lib\Thrift;
use mia\miagroup\Lib\Redis;

class MiBean extends Thrift{
    
    protected $service = "MiBean";
    
    /**
     * 增加蜜豆
     * @param unknown $param
     */
    public function add($param){
        $data = [];
        $redis = new Redis();
        //赞
        if($param['relation_type'] == 'receive_praise'){
            //给写帖子的用户+1蜜豆, （以天为周期，每天收到N个赞，最多可得3次蜜豆奖励）
            $mibean_receive_praise_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.miBeanKey.mibean_receive_praise.key'),$param['to_user_id']);
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
            $mibean_publish_pic_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.miBeanKey.mibean_publish_pic.key'),$param['user_id']);
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
            $mibean_receive_comment_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.miBeanKey.mibean_receive_comment.key'),$param['to_user_id']);
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
            //收到别人的评论+1   以天为周期，每天收到N个别人的评论，最多可得3次蜜豆奖励，首次关注有效）
            $mibean_fine_pic_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.miBeanKey.mibean_fine_pic.key'),$param['user_id']);
            $mibean_fine_pic_num = $redis->get($mibean_fine_pic_key);
            if($mibean_fine_pic_num < 3){
                //加1蜜豆
                $param['mibean'] = 1;
                $data = $this->agent('add', $param);
                if($data['code'] == 200){
                    $redis->incrBy($mibean_fine_pic_key,1);
                    $redis->expireAt($mibean_fine_pic_key,strtotime(date('Y-m-d 23:59:59')));
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
    
}