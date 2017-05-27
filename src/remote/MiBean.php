<?php
namespace mia\miagroup\Remote;

use mia\miagroup\Lib\Thrift;
use mia\miagroup\Lib\Redis;

class MiBean extends Thrift{
    
    protected $service = "MiBean";
    
    /**
     * 增加蜜豆
     * 蜜芽圈发帖：蜜豆+10（每天最多可得10次蜜豆奖励）
     * 收获赞：蜜豆+1；收到别人的评论：蜜豆+1 ，被真实粉丝关注：蜜豆+1
     * 精品贴+50   
     * 每日设置奖励上限为：总蜜豆奖励不超过300
     */
    public function add($param){
        if (empty($param['relation_type']) || empty($param['to_user_id'])) {
            return false;
        }
        $redis = new Redis();
        $mibean_total_reward_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.miBeanKey.mibean_give_way.key'),'total_reward',$param['to_user_id']);
        $mibean_total_reward = $redis->get($mibean_total_reward_key);
        if (intval($mibean_total_reward) >= 300) {
            return false;
        }
        switch ($param['relation_type']) {
            case 'receive_praise': //赞奖励
            case 'follow_me': //关注奖励
            case 'receive_comment': //评论奖励
                $param['mibean'] = 1;
                break;
            case 'publish_pic': //发布奖励
                $mibean_publish_pic_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.miBeanKey.mibean_give_way.key'),$param['relation_type'],$param['user_id']);
                $mibean_publish_pic_num = $redis->get($mibean_publish_pic_key);
                if($mibean_publish_pic_num < 10){
                    $param['mibean'] = 10;
                }
                break;
            case 'fine_pic': //加精奖励
                //验证是否送过
                $data = $this->check($param);
                if(empty($data['data'])){
                    $param['mibean'] = 50;
                }
                break;
        }
        if (intval($param['mibean']) > 0) {
            $data = $this->agent('add', $param);
            if($data['code'] == 200){
                $redis->incrBy($mibean_total_reward_key, $param['mibean']);
                $redis->expireAt($mibean_total_reward_key,strtotime(date('Y-m-d 23:59:59')));
            }
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