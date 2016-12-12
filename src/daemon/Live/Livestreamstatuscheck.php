<?php
namespace mia\miagroup\Daemon\Live;

use mia\miagroup\Data\Live\LiveStream;
use \mia\miagroup\Lib\Redis;
use \mia\miagroup\Util\RongCloudUtil;
use \mia\miagroup\Util\QiniuUtil;
use \mia\miagroup\Util\JinShanCloudUtil;
use \mia\miagroup\Data\Live\Live as LiveData;
use \mia\miagroup\Model\Live as LiveModel;
use \mia\miagroup\Util\NormalUtil;
use mia\miagroup\Util\WangSuLiveUtil;

/**
 * 10秒采集一次
 * @author user
 *
 */
class Livestreamstatuscheck extends \FD_Daemon {
    
    public function execute(){
        $redis = new Redis();
        $liveData = new LiveData();
        $rong_api = new RongCloudUtil();
        $qiniu = new QiniuUtil();
        $jinshan = new JinShanCloudUtil();
        $wangsu = new WangSuLiveUtil();
        $streamData = new LiveStream();
    
        //获取正在直播的聊天室的id
        $result = $liveData->getBatchLiveInfo();
        foreach($result as $live){
            //只检测开播5分钟之后的直播
            if(strtotime($live['start_time']) + 300 > time()){
                continue;
            }

            $framekey = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_stream_frame.key'),$live['chat_room_id']);
            $frameStatusKey = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_stream_frame_status.key'),$live['chat_room_id']);
            if($live['source']==1) {
                //七牛
                $streamStatusInfo = $qiniu->getRawStatus($live['stream_id']);
                //视频帧率
                $frame_rate = $streamStatusInfo['framesPerSecond']['video'];
                //实际码率
                $bw_rate = $streamStatusInfo['bytesPerSecond'] / 1024 * 8;
            } elseif ($live['source'] == 2) {
                $streamName = array_shift(explode('-',$live['stream_id']));
                $streamStatusInfo = $jinshan->getRawStatus($live['stream_id']);
                //视频帧率
                $frame_rate = $streamStatusInfo['app']['live'][$streamName]['video']['frame_rate'];
                //实际码率
                $bw_rate = $streamStatusInfo['app']['live'][$streamName]['bw_real'];
            } elseif ($live['source'] == 3) {
                $streamStatusInfo = $wangsu->getRawStatus($live['stream_id']);
                //视频帧率
                $frame_rate = $streamStatusInfo['fps'];
                //实际码率
                $bw_rate = $streamStatusInfo['inbandwidth'] / 1024 * 8;
            }

            //添加记录
            if (!empty($frame_rate) && !empty($bw_rate)) {
                $streamInfo['frame_rate'] = $frame_rate;
                $streamInfo['bw_rate'] = $bw_rate;

                $streamInfo['live_id'] = $live['id'];
                $streamInfo['user_id'] = $live['user_id'];
                $streamInfo['source'] = $live['source'];
                $streamInfo['stream_id'] = $live['stream_id'];
                $streamInfo['create_time'] = date('Y-m-d H:i:s');
                $res = $streamData->addStreamInfo($streamInfo);
            }

            $frameNum = $redis->zCard($framekey);
            $redis->zadd($framekey,$frameNum,$frame_rate);
            if($frameNum >= 5){
                $frameData = $redis->zRange($framekey,0,-1);
                for($i=0;$i<count($frameData);$i++){
                    if($frameData[$i] < 12){
                        $redis->incr($frameStatusKey);
                        continue;
                    }
                    if(abs($frameData[$i]-$frameData[$i+1])/$frameData[$i] >= 0.4){
                        $redis->incr($frameStatusKey);
                    }                   
                }
                //判断是否超过3次不稳定
                $frameStatusCount = $redis->get($frameStatusKey);
                if($frameStatusCount > 1){
                    if($frameStatusCount == 2){
                        $tipsText = '您的网络不稳定！请切换更好的网络!';
                    }elseif($frameStatusCount >= 3){
                        $tipsText = '您的网络非常不稳定！请切换更好的网络!';
                    }
                    $content = NormalUtil::getMessageBody(2, $live['chat_room_id'],NormalUtil::getConfig('busconf.rongcloud.fromUserId'),$tipsText);
                    // 判断是否是主播
                    $liveModel = new LiveModel();
                    $rongCloudUid = $liveModel->getRongHostUserId($live['user_id']);
                    if(!$rongCloudUid){
                        //获取主播uid失败
                        continue;
                    }
                    //发送3遍
                    for($r=0;$r<=3;$r++){
                        $rong_api->messagePublish(NormalUtil::getConfig('busconf.rongcloud.fromUserId'), $rongCloudUid, \F_Ice::$ins->workApp->config->get('busconf.rongcloud.objectNameHigh'), $content);
                    }
                }
                if(!empty($frameStatusCount)){
                    $surplusTime = $redis->ttl($frameStatusKey);
                    if($surplusTime < 0){
                        $redis->expire($frameStatusKey,\F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_stream_frame_status.expire_time'));
                    }
                }
                //清空数据
                $redis->zremrangebyrank($framekey,0,-1);
            }
          
        }
        
    }
}
