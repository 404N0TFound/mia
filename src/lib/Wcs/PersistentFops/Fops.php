<?php

namespace  Wcs\PersistentFops;

use Wcs\Config;
class Fops {

    /**
     * @var $bucket
     * 空间名
     */
    private $bucket;

    /**
     * @var $key
     * 文件名
     */
    private $key;

    /**
     * @var fops 处理参数列表，支持同时请求多个处理，用;分隔
     */
    private $fops;

    /**
     * @var $fops
     * 处理结果通知的URL
     */
    private $notifyURL;

    /**
     * @var $force
     * 是否强制执行数据处理请求,1为强制，0作为默认
     */
    private $force;

    /**
     * @var $separate
     * 转码是否分开通知，1表示每个转码指令执行完都通知notifyURL，0表示所有转码指令结束后一次性通知，默认0
     */
    private $separate;

    /**
     * 构造函数，初始化默认值
     *
     * Fops constructor.
     * @param $bucket
     * @param $key
     * @param $fops
     * @param string $notifyURL
     * @param int $force
     * @param int $separate
     */
    public function __construct(
        $bucket,
        $key,
        $fops,
        $notifyURL  = null,
        $force = 0,
        $separate = 0
    ) {
        $this->bucket = $bucket;
        $this->key = $key;
        $this->fops = $fops;
        $this->notifyURL = $notifyURL;
        $this->force = $force;
        $this->separate =$separate;
    }

    /**
     * 持久化操作函数
     *
     * @return mixed
     */
    public function exec() {
        $url = Config::WCS_MGR_URL . '/fops';

        $content = '';
        $content .= 'bucket='.\Wcs\url_safe_base64_encode($this->bucket);
        $content .= '&key=' . \Wcs\url_safe_base64_encode($this->key);
        $content .= '&fops=' .\Wcs\url_safe_base64_encode($this->fops);
        if(!empty($this->notifyURL)) {
            $content .= '&notifyURL=' .\Wcs\url_safe_base64_encode($this->notifyURL);
        }
        $content .= '&force=' . $this->force;
        $content .= '&separate=' . $this->separate;


        $signingStr = '/fops'."\n";
        $signingStr .= $content;

        $token = \Wcs\wcs_require_mac(null)->get_token($signingStr);
        $resp = $this->_post($url, $token, $content);
        return $resp;

    }

    /**
     * @param $persistentId
     * @return mixed
     */
    public static function status($persistentId) {
        $url = Config::WCS_MGR_URL . '/status/get/prefop?persistentId=' . $persistentId;
        $resp = \Wcs\http_get($url, null);

        return $resp->respBody;
    }


    /**
     * @param $url
     * @param $token
     * @param $content
     * @return mixed
     */
    private function _post($url, $token, $content) {
        $headers = array("Authorization:$token");
        $resp = \Wcs\http_post($url, $headers, $content);

        return $resp->respBody;
    }


}