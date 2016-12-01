<?php
namespace  Wcs;

use Wcs\Http\Response;


/**
 * @param $str
 * @return mixed
 */
function url_safe_base64_encode($str)
{
    $find = array('+', '/');
    $replace = array('-', '_');
    return str_replace($find, $replace, base64_encode($str));
}


/**
 * @param $str
 * @return string
 */
function url_safe_base64_decode($str)
{
    $find = array('-', '_');
    $replace = array('+', '/');
    return base64_decode(str_replace($find, $replace, $str));
}

/**
 * @param $str
 * @param $token
 * @return bool
 */
function str_start_with($str, $token)
{
    return stripos($str, $token) == 0;
}

function get_user_agent()
{
    $sdkInfo = "WCS PHP SDK /" . Config::WCS_SDK_VER . " (http://wcs.chinanetcenter.com/)";

    $systemInfo = php_uname("s");
    $machineInfo = php_uname("m");

    $envInfo = "($systemInfo/$machineInfo)";

    $phpVer = phpversion();

    $ua = "$sdkInfo $envInfo PHP/$phpVer";
    return $ua;
}

function http_get($url, $headers, $opt = null)
{
    $ch = curl_init();
    $options = array(
        CURLOPT_USERAGENT => get_user_agent(),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => false,
        CURLOPT_URL => $url,
        CURLOPT_TIMEOUT => Config::WCS_TIMEOUT
    );

    if($opt) {
        foreach ($opt as $key => $value) {
            $options[$key] = $value;
        }
    }

    if (!empty($headers)) {
        $options[CURLOPT_HTTPHEADER] = $headers;
    }

    curl_setopt_array($ch, $options);

    $result = curl_exec($ch);

    $ret = new Response();
    $errno = curl_errno($ch);

    //错误状态码
    if ($errno !== 0) {
        $ret->code = $errno;
        $ret->message = curl_error($ch);
        curl_close($ch);
        return $ret;
    }

    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    //分割响应头部和内容
    $responseArray = explode("\r\n\r\n", $result);
    $responseArraySize = sizeof($responseArray);
    if ($responseArraySize == 1) {
        $respBody = $responseArray[0];
    } else {
        $respHeader = $responseArray[0];
        $respBody = $responseArray[1];
        $ret->respHeader = $respHeader;
    }
    $ret->respBody = $respBody;
    $ret->code = $code;

    //超时判断
    if ($ret->code == 28) {
        $ret->respBody = "请求超时！";
    }

    return $ret;
}


function http_post($url, $headers, $fields, $opt = null)
{

    $ch = curl_init();

    $options= array(
        CURLOPT_USERAGENT => get_user_agent(),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => false,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_URL => $url,
        CURLOPT_TIMEOUT => Config::WCS_TIMEOUT,
    );

    if($opt) {
        foreach ($opt as $key => $value) {
           $options[$key] = $value;
        }
    }

    if (!empty($headers)) {
        $options[CURLOPT_HTTPHEADER] = $headers;
    }

    if (!empty($fields)) {
        $options[CURLOPT_POSTFIELDS] = $fields;
    }


    curl_setopt_array($ch, $options);


    $result = curl_exec($ch);

    $ret = new Response();
    $errno = curl_errno($ch);

    //错误状态码
    if ($errno !== 0) {
        $ret->code = $errno;
        $ret->message = curl_error($ch);
        curl_close($ch);
        return $ret;
    }

    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    //分割响应头部和内容
    $responseArray = explode("\r\n\r\n", $result);
    $responseArraySize = sizeof($responseArray);
    $respHeader = $responseArray[$responseArraySize - 2];
    $respBody = $responseArray[$responseArraySize - 1];

    $ret->code = $code;
    $ret->respHeader = $respHeader;
    $ret->respBody = $respBody;

    //超时判断
    if ($ret->code == 28) {
        $ret->respBody = "请求超时！";
    }

    return $ret;
}


function build_public_url($bucketName, $fileName)
{
    $HTTP_PREFIX = 'http://';

    if (str_start_with(Config::WCS_GET_URL, $HTTP_PREFIX)) {
        $baseUrl = $HTTP_PREFIX . $bucketName . '.' . substr(Config::WCS_GET_URL, strlen($HTTP_PREFIX));
    } else {
        $baseUrl = $bucketName . '.' . Config::WCS_GET_URL;
    }

        $baseUrl .= '/' . $fileName;

        return $baseUrl;
    }





