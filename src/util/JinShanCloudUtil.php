<?php
namespace mia\miagroup\Util;

use mia\miagroup\Util\Ks3ClientUtil;
use mia\miagroup\Lib\Redis;
use mia\miagroup\Util\NormalUtil;

class JinShanCloudUtil
{
    private $_config;
    private $_stream;
    private $_nonce;
    private $_vdoid;
    private $_signature;
    private $_expire;
    private $_public=0;
    private $_signArr = [];
    private $_buildQuery = [];
    private $_client;

    public function __construct()
    {
        $this->_config  = \F_Ice::$ins->workApp->config->get('busconf.jinshan');
        $this->_stream  = $this->_config['live_prefix'];
        $this->_nonce   = $this->_getString();
        $this->_vdoid   = $this->_getString();
        $this->_expire  = time()+86400;

        $this->_signArr = [
            'vdoid'  => $this->_vdoid,
            'nonce'  => $this->_nonce,
            'public' => $this->_public,
        ];
     
        $this->_signature  = $this->_getSign($this->_signArr);
        $this->_buildQuery = array_merge(
            [
                'signature' => $this->_signature,
                'accesskey' => $this->_config['access_key'],
                'expire'    => $this->_expire,
            ],
            $this->_signArr
        );

        $this->_client = new Ks3ClientUtil($this->_config['access_key'],$this->_config['secret_key'],$this->_config['endpoint']);
    }


    public function createStream($createId)
    {
        $data       = [];
        $streamId   = $this->_getStremId($createId);
        $query      = $this->_getHttpQuery();
        $streamname = $this->_stream.$createId;
        $data = [
            'id'      => $streamId,
            'publish' => 'rtmp://'.$this->_config['live_host']['publish']['rtmp'].'/live/'.$streamname.'?'.$query,
        ];
        $streamInfoKey = sprintf(NormalUtil::getConfig('busconf.rediskey.liveKey.live_jinshan_stream_info.key'), $streamId);
        $redis = new Redis();
        $redis->setex($streamInfoKey, $data, NormalUtil::getConfig('busconf.rediskey.liveKey.live_jinshan_stream_info.expire_time'));
        return $data;
    }


    public function getStreamInfoByStreamId($streamId)
    {
        $streamInfoKey = sprintf(NormalUtil::getConfig('busconf.rediskey.liveKey.live_jinshan_stream.key'), $streamId);
        $data = [];
        $redis = new Redis();
        if($redis->exists($streamInfoKey)){
            $data = $redis->get($streamInfoKey);
        }
        return $data;
    }


    public function getLiveUrls($streamId)
    {
        $streamInfo = $this->_getVdoidAndStreamname($streamId);
        $data = [];
        if($streamInfo){
            $data = [
                'rtmp' => 'rtmp://' . $this->_config['live_host']['live']['rtmp'].'/live/' . $streamInfo['streamname'],
                'hls'  => 'http://' . $this->_config['live_host']['live']['hls'].'/live/' . $streamInfo['streamname'] . "/index.m3u8",
                'hdl'  => 'http://' . $this->_config['live_host']['live']['hdl'].'/live/' . $streamInfo['streamname'] . ".flv",
            ];
        }
        return $data;
    }

    public function getPalyBackUrls($streamId)
    {
        $streamInfo = $this->_getVdoidAndStreamname($streamId);
        $data = [];
        if($streamInfo){
            $data = [
                'hls' => 'http://' . $this->_config['live_paly_back'].'/record/live/'.$streamInfo['streamname'].'/hls/'.$streamId . '.m3u8',
            ];
        }
        return $data;

    }

    public function getSnapShot($streamId,$format = '.jpg')
    {
        $streamInfo = $this->_getVdoidAndStreamname($streamId);
        $data = [];
        if($streamInfo){
            $args = [
                'Bucket'=>$this->_config['image_bucket'],
                'Options'=>[
                    "prefix"=>'record/live/'.$streamInfo['streamname'].'/picture',
                    "delimiter"=>$streamInfo['streamname'].'-'.time().$format,
                    "max-keys"=>5
                ]
            ];
            $result = $this->_client->listObjects($args);
            if(isset($result['Contents'][0]) && !empty($result['Contents'][0])){
                $data['origin'] = 'http://'.$this->_config['live_snap_shot'];
                $data[$result['Contents'][0]['Size']] = 'http://'.$this->_config['live_snap_shot'].'/'.$result['Contents'][0]['Key'];
            }
        }
        return $data;
    }

    public function getSaveAsMp4($streamId,$format = '.mp4')
    {
        $streamInfo = $this->_getVdoidAndStreamname($streamId);
        $data = [];
        if($streamInfo){
            $args = [
                'Bucket'=>$this->_config['live_bucket'],
                'Options'=>[
                    "prefix"=>$streamInfo['vdoid'],
                    "delimiter"=>$streamInfo['vdoid'].$format,
                    "max-keys"=>1
                ]
            ];
            $result = $this->_client->listObjects($args);
            if(isset($result['Contents'][0]) && !empty($result['Contents'][0])){
                $data['targetUrl'] = 'http://'.$this->_config['live_paly_back'].'/'.$result['Contents'][0]['Key'];
            }
        }
        return $data;
    }


    /**
     * 获取签名
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

    private function _getString($length=8)
    {
        $str = "";
        $chars = "AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz0123456789";
        $max = strlen($chars) - 1;
        for($i=0;$i<$length;$i++){
            $str.=$chars[rand(0,$max)];
        }
        return $str;

    }

    private function _getStremId($createId)
    {
        return $this->_stream.$createId.'-'.$this->_vdoid;
    }

    private function _getHttpQuery()
    {
        return http_build_query($this->_buildQuery);
    }

    private function _getVdoidAndStreamname($streamId)
    {
        $idInfo = explode('-', $streamId);
        $data = [];
        if(isset($idInfo[0]) && isset($idInfo[1])){
            return $data = [
                'streamname' => $idInfo[0],
                'vdoid'      => $idInfo[1]
            ];
        }
        return $vdoid;
    }

}