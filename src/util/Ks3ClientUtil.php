<?php

namespace mia\miagroup\Util;


class Ks3ClientUtil
{

    private $_client;
    private $_config;

    public $defined = false;
    public $vhost = false;
    public $log = false;
    public $display_log = false;
    public $log_path = '';
    public $use_https = false;
    public $debug_mode = false;

    public function __construct($ak,$sk,$endpoint)
    {
        if(!$this->defined){
            $this->syncDefine();
        }
        require_once __DIR__ . '/../lib/ks3sdk/Ks3Client.class.php';
        $this->_client = new \Ks3Client($ak,$sk,$endpoint);
        return $this->_client;
    }

    public function __call($method, $parameters)
    {
        if(is_null($this->_client)){
            throw new \ErrorException('Undefined  method '.get_called_class().'->'.$method);
        }
        return call_user_func_array([$this->_client, $method], $parameters);
    }

    private  function syncDefine()
    {
        if(!defined("KS3_API_VHOST")){
            //是否使用VHOST
            define("KS3_API_VHOST",$this->vhost);
        }

        if(!defined("KS3_API_LOG")){
            //是否开启日志(写入日志文件)
            define("KS3_API_LOG",$this->log);
        }

        if(!defined("KS3_API_DISPLAY_LOG")){
            //是否显示日志(直接输出日志)
            define("KS3_API_DISPLAY_LOG", $this->display_log);
        }

        if(!defined("KS3_API_LOG_PATH")){
            //定义日志目录(默认是该项目log下)
            define("KS3_API_LOG_PATH", $this->log_path);
        }

        if(!defined("KS3_API_USE_HTTPS")){
            //是否使用HTTPS
            define("KS3_API_USE_HTTPS",$this->use_https);
        }

        if(!defined("KS3_API_DEBUG_MODE")){
            //是否开启curl debug模式
            define("KS3_API_DEBUG_MODE",$this->debug_mode);
        }

        $this->defined = true;
    }


}