<?php
namespace mia\miagroup\Lib;

class RemoteCurl {
    private $_config_info;
    private $_remote_name;
    private $_url; //接口URL
    private $_method; //请求协议
    private $_charset = 'utf8'; //接口返回数据编码
    private $_data_key = 'data'; //json结果集，数据key
    private $_code_key = 'code'; //json结果集，错误码key
    private $_msg_key = 'msg'; //json结果集，错误信息key
    private $_success_code = 0; //json结果集，成功正确码
    private $_result_format = true; //返回结果是否格式化解析
    private $_time_out = 1; //连接超时时间，默认1秒
    
    public function __construct($remote_name) {
        $this->set_remote_info($remote_name);
    }

    /**
     * 发送请求并返回数据
     * @param array $arguments 请求的数据
     * @param array|string $remote_name 接口信息或接口名
     * @return string 返回接口返回的数据
     */
    public function curl_remote($url_path, $arguments) {
        try {
            $request_time = gettimeofday(true);
            if (is_array($arguments)) {
                foreach ($arguments as $key => $value) {
                    if (is_string($value) && strpos($value, '@') === 0) { //防止@被解析成文件
                        $arguments[$key] = ' ' . $value;
                    }
                }
            }
            if ($this->_url && $this->_method && $this->_charset) {
                $request_url = $this->_url . $url_path;
                //post get 分别处理
                if ($this->_method == 'get') {
                    //请求的URL处理，如果是GET，需要将参数变成get字串拼进URL
                    $concat_char = '?';
                    if (strpos($request_url, '?')) {
                        $concat_char = '&';
                    }
                    $arguments = http_build_query($arguments);
                    $request_url = $request_url . $concat_char . $arguments;
                    $return_data = $this->get($request_url, $this->_charset);
                } else {
                    $return_data = $this->post($request_url, $arguments, $this->_charset);
                }
            }
            if (empty($return_data)) {
                throw new \Exception('empty data', -1);
            }
            $return_data = json_decode($return_data, true);
            if ($this->_result_format !== false) {
                if (isset($return_data[$this->_code_key]) && $return_data[$this->_code_key] != $this->_success_code) {
                    throw new \Exception(strval($return_data[$this->_msg_key]), $return_data[$this->_code_key]);
                }
                $return_data = isset($return_data[$this->_data_key]) ? $return_data[$this->_data_key] : array();
            }
            \F_Ice::$ins->mainApp->logger_remote->info(array(
                'third_server'  =>  $this->_remote_name,
                'type'          =>  'INFO',
                'request_param' =>  $arguments,
                'response_code' =>  $return_data[$this->_code_key],
                'response_msg'  =>  '',
                'request_url'   =>  $this->_url,
                'resp_time'     =>  number_format((gettimeofday(true) - $request_time), 4),
            ));
        } catch (\Exception $e) {
            \F_Ice::$ins->mainApp->logger_remote->warn(array(
                'third_server'  =>  $this->_remote_name,
                'type'          =>  'ERROR',
                'exception'     => get_class($e),
                'message'       => $e->getMessage(),
                'code'          => $e->getCode(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'trace'         => $e->getTraceAsString(),
            ));
        }
        return $return_data;
    }

    /**
     * 设置接口请求信息
     * @param array $remote_info 接口信息，数组，包含url-地址、method方法、charset编码
     */
    private function set_remote_info($remote_name) {
        $this->_remote_name = $remote_name;
        $this->_config_info = $this->config = \F_Ice::$ins->workApp->config->get('remote_curl');
        $remote_info = $this->_config_info[$remote_name];
        if ($remote_info) {
            $this->_url = $remote_info['url'] ? $remote_info['url'] : '';
            $this->_method = $remote_info['method'] ? $remote_info['method'] : 'post';
            if (isset($remote_info['charset']) && !empty($remote_info['charset'])) {
                $this->_charset = $remote_info['charset'];
            }
            if (isset($remote_info['data_key']) && !empty($remote_info['data_key'])) {
                $this->_data_key = $remote_info['data_key'];
            }
            if (isset($remote_info['code_key']) && !empty($remote_info['code_key'])) {
                $this->_code_key = $remote_info['code_key'];
            }
            if (isset($remote_info['msg_key']) && !empty($remote_info['msg_key'])) {
                $this->_msg_key = $remote_info['msg_key'];
            }
            if (isset($remote_info['success_code']) && !empty($remote_info['success_code'])) {
                $this->_success_code = $remote_info['success_code'];
            }
            if (isset($remote_info['result_format'])) {
                $this->_result_format = $remote_info['result_format'];
            }
            if (isset($remote_info['time_out'])) {
                $this->_time_out = $remote_info['time_out'];
            }
        }
    }

    /**
     * 发送get请求并返回请求结果
     */
    public function get($url, $charset = 'utf8') {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_time_out);
        $return_data = curl_exec($ch);
        if (!$return_data) {
            $curl_info = curl_getinfo($ch);
            if ($curl_info['http_code'] == 0 || $curl_info['http_code'] >= 400) {
                throw new \Exception('http error', $curl_info['http_code']);
            }
        }
        if ($charset != 'utf8') {
            $return_data = iconv($charset, 'utf8', $return_data);
        }
        curl_close($ch);
        return $return_data;
    }

    /**
     * 发送post请求并返回请求结果
     *
     */
    public function post($url, $arguments, $charset = 'utf8') {
        if(is_array($arguments)){
            $post_data =  http_build_query($arguments);
        }else{
            $post_data = $arguments;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_time_out);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $return_data = curl_exec($ch);
        if (!$return_data) {
            $curl_info = curl_getinfo($ch);
            if ($curl_info['http_code'] == 0 || $curl_info['http_code'] >= 400) {
                throw new \Exception('http error', $curl_info['http_code']);
            }
        }
        if ($charset != 'utf8') {
            $return_data = iconv($charset, 'utf8', $return_data);
        }
        curl_close($ch);
        return $return_data;
    }
}
