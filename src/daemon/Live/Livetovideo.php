<?php
namespace mia\miagroup\Daemon\Live;

use mia\miagroup\Util\QiniuUtil;
use mia\miagroup\Service\Subject;
use mia\miagroup\Model\Live as LiveModel;
use Qiniu\HttpRequest;
use mia\miagroup\Lib\Redis;
use mia\miagroup\Util\NormalUtil;

/**
 *把直播回放移到视频资源中，关发表回放帖子
 *
 * 
 */
class Livetovideo extends \FD_Daemon {

    public function execute() {
        $qiniu = new QiniuUtil();
        $subject = new Subject();
        $liveModel = new LiveModel();
        $redis = new Redis();
        
        // 获取待转换成视频的直播回放
        $live_list_key = \F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_to_video_list.key');
        $redis = new Redis();
        $liveId = $redis->lindex($live_list_key, -1);
        $liveInfo = $liveModel->getLiveInfoById($liveId);
        if ($liveInfo['subject_id'] > 0 || $liveInfo['status'] != 4) {
            //剔除已转码完成的
            $redis->rpop($live_list_key);
            return;
        }

        // m3u8转换成MP4
        $live_to_video_key = sprintf(NormalUtil::getConfig('busconf.rediskey.liveKey.live_to_video.key'), $liveInfo['id']);
        if (!$redis->exists($live_to_video_key)) {
            $tomp4 = $qiniu->getSaveAsMp4($liveInfo['stream_id']);
            if (!isset($tomp4['targetUrl']) || empty($tomp4['targetUrl'])) {
                echo 'm3u8转换成MP4失败' . "\n";
                continue;
            }
            $data = json_encode($tomp4);
            $redis->setex($live_to_video_key, $data, NormalUtil::getConfig('busconf.rediskey.liveKey.live_to_video.expire_time'));
        }
        
        // 判断是否已经转换完成
        $res_api = HttpRequest::send('GET', NormalUtil::getConfig('busconf.qiniu.prefop'), ['id' => $redis->get($live_to_video_key)['persistentId']]);
        if ($res_api->code == 200 && !json_decode($res_api->raw_body, true)['code']) {
            // 从七牛mia_live-live移到video资源目录下
            $mvToVideo = $qiniu->fetchBucke($redis->get($live_to_video_key)['targetUrl']);
            if (!isset($mvToVideo['key']) || empty($mvToVideo['key'])) {
                echo '资源移动失败' . "\n";
                continue;
            }
            
            // 重命名文件
            $renameVideo = $qiniu->rename('video', $mvToVideo['key'], $redis->get($live_to_video_key)['fileName']);
            if (!$renameVideo) {
                echo '重命名文件失败' . "\n";
                continue;
            }
            
            // 发帖子
            $subjectInfo['user_info'] = ['user_id' => $liveInfo['user_id']];
            $subjectInfo['video_url'] = $redis->get($live_to_video_key)['fileName'];
            $result = $subject->issue($subjectInfo);
            if (!isset($result['data'])) {
                echo '发帖子失败 直播 id is ' . $liveInfo['id'] . "\n";
                continue;
            }
            
            // 更新直播
            $updateData[] = ['subject_id', $result['data']['id']];
            $res = $liveModel->updateLiveById($liveInfo['id'], $updateData);
            if (!$res) {
                echo '更新失败 直播 id is ' . $liveInfo['id'] . "\n";
                continue;
            }
            
            $redis->del($live_to_video_key);
            $redis->rpop($live_list_key);
        }
    }
}