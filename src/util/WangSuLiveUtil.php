<?php
namespace mia\miagroup\Util;

use mia\miagroup\Lib\Redis;

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
        $this->_config = F_Ice::$ins->workApp->config->get('busconf.wangsu');
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
     * 截图
     */
    public function getSnapShot($streamId, $format = '.jpg')
    {

    }

    /**
     * 生成视频
     */
    public function getSaveAsMp4($streamId, $format = '.mp4')
    {

    }

    /**
     * 获取直播状态
     **/
    public function getStatus($streamId)
    {

    }

    /**
     * @param $streamId
     * @return mixed|null|string
     */
    public function getRawStatus($streamId)
    {
        $streamInfo = $this->_getVdoidAndStreamname($streamId);
        $streamName = $streamInfo['streamname'];
        $url = $this->_config['live_stream_status'] . 'name=' . $streamName;
        $result = json_decode($this->_curlGet($url), true);
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

    /**
     * @param $url
     * @return mixed
     */
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