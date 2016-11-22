<?php
namespace mia\miagroup\Util;

use mia\miagroup\Lib\Redis;

class WangSuLiveUtil
{
    private $_config;
    private $_stream;
    private $_nonce;
    private $_vdoid;
    private $_signature;
    private $_expire;
    private $_public = 0;
    private $_signArr = [];
    private $_buildQuery = [];
    private $_client;

    /**
     * WangSuLiveUtil constructor.
     * 初始配置
     */
    public function __construct()
    {

    }

    /**
     * 创建推流地址
     * @param $createId
     * @return array
     */
    public function createStream($createId)
    {
        $data = [];
        $streamId = $this->_getStremId($createId);
        $query = $this->_getHttpQuery();
        $streamname = $this->_stream . $createId;
        $data = [
            'id' => $streamId,
            'publish' => 'rtmp://' . $this->_config['live_host']['publish']['rtmp'] . '/live/' . $streamname . '?' . $query,
        ];
        $streamInfoKey = sprintf(NormalUtil::getConfig('busconf.rediskey.liveKey.live_jinshan_stream_info.key'), $streamId);
        $redis = new Redis();
        $redis->setex($streamInfoKey, $data, NormalUtil::getConfig('busconf.rediskey.liveKey.live_jinshan_stream_info.expire_time'));
        return $data;
    }

    /**
     * @param $streamId
     * @return array|mixed|null|string
     */
    public function getStreamInfoByStreamId($streamId)
    {
        $streamInfoKey = sprintf(NormalUtil::getConfig('busconf.rediskey.liveKey.live_jinshan_stream_info.key'), $streamId);
        $data = [];
        $redis = new Redis();
        if ($redis->exists($streamInfoKey)) {
            $data = $redis->get($streamInfoKey);
        }
        return $data;
    }

    /**
     * @param $streamId
     * @return array
     */
    public function getLiveUrls($streamId)
    {
        $streamInfo = $this->_getVdoidAndStreamname($streamId);
        $data = [];
        if ($streamInfo) {
            $data = [
                'rtmp' => 'rtmp://' . $this->_config['live_host']['live']['rtmp'] . '/live/' . $streamInfo['streamname'],
                'hls' => 'http://' . $this->_config['live_host']['live']['hls'] . '/live/' . $streamInfo['streamname'] . "/index.m3u8",
                'hdl' => 'http://' . $this->_config['live_host']['live']['hdl'] . '/live/' . $streamInfo['streamname'] . ".flv",
            ];
        }
        return $data;
    }

    /**
     * @param $streamId
     * @return array
     */
    public function getPalyBackUrls($streamId)
    {
        $streamInfo = $this->_getVdoidAndStreamname($streamId);
        $data = [];
        if ($streamInfo) {
            $data = [
                'hls' => 'http://' . $this->_config['live_paly_back'] . '/record/live/' . $streamInfo['streamname'] . '/hls/' . $streamId . '.m3u8',
            ];
        }
        return $data;

    }

    /**
     * @param $streamId
     * @param string $format
     * @return array
     */
    public function getSnapShot($streamId, $format = '.jpg')
    {
        $streamInfo = $this->_getVdoidAndStreamname($streamId);
        $data = [];
        if ($streamInfo) {
            $args = [
                'Bucket' => $this->_config['image_bucket'],
                'Options' => [
                    "prefix" => 'record/live/' . $streamInfo['streamname'] . '/picture',
                    "delimiter" => $streamInfo['streamname'] . '-' . time() . $format,
                    "max-keys" => 5
                ]
            ];
            $result = $this->_client->listObjects($args);
            if (isset($result['Contents'][0]) && !empty($result['Contents'][0])) {
                $data['origin'] = 'http://' . $this->_config['live_snap_shot'];
                $data[$result['Contents'][0]['Size']] = 'http://' . $this->_config['live_snap_shot'] . '/' . $result['Contents'][0]['Key'];
            }
        }
        return $data;
    }

    /**
     * @param $streamId
     * @param string $format
     * @return array
     */
    public function getSaveAsMp4($streamId, $format = '.mp4')
    {
        $streamInfo = $this->_getVdoidAndStreamname($streamId);
        $data = [];
        if ($streamInfo) {
            $args = [
                'Bucket' => $this->_config['live_bucket'],
                'Options' => [
                    "prefix" => $streamInfo['vdoid'],
                    "delimiter" => $streamInfo['vdoid'] . $format,
                    "max-keys" => 1
                ]
            ];
            $result = $this->_client->listObjects($args);
            if (isset($result['Contents'][0]) && !empty($result['Contents'][0])) {
                $data['targetUrl'] = 'http://' . $this->_config['live_paly_back'] . '/' . $result['Contents'][0]['Key'];
            }
        }
        return $data;
    }

    /**
     * 获取直播状态
     *
     * @return void
     * @author
     **/
    public function getStatus($streamId)
    {
        $result = $this->getRawStatus($streamId);

        $returnValue = 'connected';
        $liveStatusKey = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_stream_status.key'), $streamId);
        $redis = new Redis();
        if (empty($result)) {
            //策略： 直播状态首次中断并不立即返回中断状态，而是再次检测到中断并且相差5秒以上，才中断
            //此策略为防止第三方瞬间返回中断然而实际没中断问题
            //获取数量
            $liveStreamStatus = $redis->get($liveStatusKey);
            $lastDisconnectedTime = intval($liveStreamStatus);
            if ($lastDisconnectedTime > 0) {
                if (time() - $lastDisconnectedTime >= 600) {
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
     * 获取签名
     * @param $data
     * @param string $method
     * @return string
     */
    private function _getSign($data, $method = 'GET')
    {
        $toSign = "$method\n$this->_expire";
        //按照字典序排列
        if (is_array($data)) {
            $toSign .= "\n";
            ksort($data);
            $keys = array_keys($data);
            foreach ($keys as $key) {
                $toSign .= $key . '=' . $data[$key] . '&';
            }
            $toSign = trim($toSign, '&');
        }
        $sign = base64_encode(hash_hmac("sha1", $toSign, $this->_config['secret_key'], true));
        return $sign;
    }

    /**
     * @param int $length
     * @return string
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
     * @param $createId
     * @return string
     */
    private function _getStremId($createId)
    {
        return $this->_stream . $createId . '-' . $this->_vdoid;
    }

    /**
     * @return null|string
     */
    private function _getHttpQuery()
    {
        return http_build_query($this->_buildQuery);
    }

    /**
     * @param $streamId
     * @return array
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
        return $vdoid;
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