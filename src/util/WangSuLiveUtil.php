<?php
namespace mia\miagroup\Util;

use mia\miagroup\Lib\Redis;
use Wcs\SrcManage\FileManager;

class WangSuLiveUtil
{
    private $_config;
    private $_vdoid;
    private $_stream;

    /**
     * WangSuLiveUtil constructor.
     * 初始配置
     */
    public function __construct()
    {
        $this->_config = \F_Ice::$ins->workApp->config->get('busconf.wangsu');
        $this->_vdoid = $this->_getString();
        $this->_stream = $this->_config['live_prefix'];
    }

    /**
     * 创建推流地址
     * @param $createId
     * @return array
     */
    public function createStream($createId)
    {
        $data = [];
        //streamId
        $streamId = $this->_getStremId($createId);
        //流名
        $streamname = $this->_stream . $createId;
        $data = [
            'id' => $streamId,
            'publish' => 'rtmp://' . $this->_config['live_host']['publish']['rtmp'] . '/' . $streamname,
        ];
        $streamInfoKey = sprintf(NormalUtil::getConfig('busconf.rediskey.liveKey.live_wangsu_stream_info.key'), $streamId);
        $redis = new Redis();
        $redis->setex($streamInfoKey, $data, NormalUtil::getConfig('busconf.rediskey.liveKey.live_wangsu_stream_info.expire_time'));
        return $data;
    }

    /**
     * 获取推流流信息
     */
    public function getStreamInfoByStreamId($streamId)
    {
        $streamInfoKey = sprintf(NormalUtil::getConfig('busconf.rediskey.liveKey.live_wangsu_stream_info.key'), $streamId);
        $data = [];
        $redis = new Redis();
        if ($redis->exists($streamInfoKey)) {
            $data = $redis->get($streamInfoKey);
        }
        return $data;
    }

    /**
     * 获取拉流列表
     */
    public function getLiveUrls($streamId)
    {
        $streamInfo = $this->_getVdoidAndStreamname($streamId);
        $data = [];
        if ($streamInfo) {
            $data = [
                'rtmp' => $this->_config['live_host']['live']['rtmp'] . '/' . $streamInfo['streamname'],
                'hls' => $this->_config['live_host']['live']['hls'] . '/' . $streamInfo['streamname'] . "/playlist.m3u8",
                'hdl' => $this->_config['live_host']['live']['hdl'] . '/' . $streamInfo['streamname'] . ".flv",
            ];
        }
        return $data;
    }

    /**
     * 获取回放地址
     */
    public function getPalyBackUrls($streamId, $timeStart, $timeEnd)
    {
        $streamInfo = $this->_getVdoidAndStreamname($streamId);
        $data = [];
        if ($streamInfo && $timeStart && $timeEnd) {
            $data = [
                //参数格式wsStreamTimeABS=20130815140000&wsStreamTimeABSEnd = 20130815143000
                'hls' => $this->_config['live_host']['live']['hls'] . '/' . $streamInfo['streamname'] . "/playlist.m3u8?wsStreamTimeABS=" . $timeStart . "&wsStreamTimeABSEnd = " . $timeEnd,
            ];
        }
        return $data;
    }

    /**
     * 获取截图
     */
    public function getSnapShot($streamId)
    {
        $streamInfo = $this->_getVdoidAndStreamname($streamId);
        $streamname = $streamInfo['streamname'];

        $wsImg = new FileManager();
        //存储格式wslive-流名--20161129142059.jpg。每场直播的流名都不一样，以此来区分。
        $imgList = $wsImg->lists($this->_config['img_bucket'], 5, "wslive-" . $streamname . "--");
        $result = json_decode($imgList, true);

        if (isset($result['items'][0]) && !empty($result['items'][0])) {
            $data['origin'] = $this->_config['live_snap_shot'];
            $data[$result['items'][0]['fsize']] = $this->_config['live_snap_shot'] . '/' . $result['items'][0]['key'];
        }
        return $data;
    }

    /**
     * 获取生成的视频
     */
    public function getSaveAsMp4($streamId)
    {
        $streamInfo = $this->_getVdoidAndStreamname($streamId);
        $streamname = $streamInfo['streamname'];

        $wsImg = new FileManager();
        //存储格式wslive-流名--20161130113043.mp4。每场直播的流名都不一样，以此来区分。
        $videoList = $wsImg->lists($this->_config['video_bucket'], 5, "wslive-" . $streamname . "--");
        $result = json_decode($videoList, true);

        //多个视频的话，待完善
        if (isset($result['items'][0]) && !empty($result['items'][0])) {
            $data['origin'] = $this->_config['live_video'];
            $data[$result['items'][0]['fsize']] = $this->_config['live_video'] . '/' . $result['items'][0]['key'];
        }
        return $data;
    }

    /**
     * 获取直播状态
     **/
    public function getStatus($streamId)
    {
        $result = $this->getRawStatus($streamId);

        $returnValue = 'connected';
        $liveStatusKey = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_stream_status.key'), $streamId);
        $redis = new Redis();
        if (empty($result)) {
            //断开状态
            //策略： 直播状态首次中断并不立即返回中断状态，而是再次检测到中断并且相差5秒以上，才中断
            //此策略为防止第三方瞬间返回中断然而实际没中断问题
            //获取数量
            $liveStreamStatus = $redis->get($liveStatusKey);
            $lastDisconnectedTime = intval($liveStreamStatus);
            if ($lastDisconnectedTime > 0) {
                if (time() - $lastDisconnectedTime >= 5) {
                    $returnValue = 'disconnected';
                }
            } else {
                $redis->setex($liveStatusKey, time(), \F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_stream_status.expire_time'));
            }
        } else {
            $redis->setex($liveStatusKey, time(), \F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_stream_status.expire_time'));
        }
        return $returnValue;
    }

    /**
     * 获取流信息
     */
    public function getRawStatus($streamId)
    {
        $streamInfo = $this->_getVdoidAndStreamname($streamId);
        $streamname = $streamInfo['streamname'];
        $r = time();
        $query_arr = [
            'n' => $this->_config['live_stream_api']['protal_username'],//平台帐号名
            'r' => $r,//唯一随机字符
            'u' => $this->_config['live_host']['publish']['rtmp_q'],//所需查询的推流域名
            'k' => md5($r . $this->_config['live_stream_api']['key']),//md5(r+key)
            'channel' => $streamname,
        ];
        $query_str = http_build_query($query_arr);
        $url = $this->_config['live_stream_status'] . $query_str;echo $url;
        $result = json_decode($this->_curlGet($url), true);
        var_export($result);
        return $result;
    }

    /**
     * 生成随机字符串
     */
    private function _getString($length = 8)
    {
        $str = "";
        $chars = "AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz0123456789";
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[rand(0, $max)];
        }
        return $str;
    }

    /**
     * 生成stremId
     */
    private function _getStremId($createId)
    {
        return $this->_stream . $createId . '-' . $this->_vdoid;
    }

    /**
     * 获取streamName
     */
    private function _getVdoidAndStreamname($streamId)
    {
        $idInfo = explode('-', $streamId);
        $data = [];
        if (isset($idInfo[0]) && isset($idInfo[1])) {
            return $data = [
                'streamname' => $idInfo[0],
                'vdoid' => $idInfo[1]
            ];
        }
        return $data;
    }

    private function _curlGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;

    }
}