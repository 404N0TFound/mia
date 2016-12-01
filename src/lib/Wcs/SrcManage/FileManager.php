<?php
namespace  Wcs\SrcManage;

use Wcs;
use Wcs\Config;


class FileManager
{
     /**
     * 移动资源
     * @param $bucketSrc //源空间
     * @param $bucketDst //目标空间
     * @param $keySrc
     * @param $keyDst
     * @return Wcs\Http\Response
     */
    public function move($bucketSrc, $keySrc, $bucketDst, $keyDst) {
        //encodeEntryUrl bucket:key
        $paramSrc = $bucketSrc . ":" . $keySrc;
        $paramSrc = Wcs\url_safe_base64_encode($paramSrc);
        $paramDst = $bucketDst . ":" . $keyDst;
        $paramDst = Wcs\url_safe_base64_encode($paramDst);

        $url = Config::WCS_MGR_URL . "/move/" . $paramSrc . "/" . $paramDst;
        $signingStr = "/move/" . $paramSrc . "/" . $paramDst . "\n";

        $token = Wcs\wcs_require_mac(null)->get_token($signingStr);

        $resp = $this->_post($url, $token);

        return $resp;
    }

     /**
     * 复制资源
     * @param $bucketSrc //源空间
     * @param $bucketDst //目标空间
     * @param $keySrc
     * @param $keyDst
     * @return Wcs\Http\Response
     */
    public function copy($bucketSrc, $keySrc, $bucketDst, $keyDst) {
        //encodeEntryUrl bucket:key
        $paramSrc = $bucketSrc . ":" . $keySrc;
        $paramSrc = Wcs\url_safe_base64_encode($paramSrc);
        $paramDst = $bucketDst . ":" . $keyDst;
        $paramDst = Wcs\url_safe_base64_encode($paramDst);

        $url = Config::WCS_MGR_URL . "/copy/" . $paramSrc . "/" . $paramDst;
        $signingStr = "/copy/" . $paramSrc . "/" . $paramDst . "\n";

        $token = Wcs\wcs_require_mac(null)->get_token($signingStr);

        $resp = $this->_post($url, $token);

        return $resp;

    }

    /**
     * 删除文件
     * @param $bucketName
     * @param $fileKey
     * @return mixed
     */
    public function delete($bucketName, $fileKey)
    {
        $entry = $bucketName . ':' . $fileKey;
        $encodedEntry = \Wcs\url_safe_base64_encode($entry);

        $url = Config::WCS_MGR_URL . '/delete/' . $encodedEntry;

        $token = \Wcs\get_file_delete_token($bucketName, $fileKey);

        return $this->_post($url, $token);

    }

    /**
     * 获取文件信息
     * @param $bucketName
     * @param $fileKey
     * @return mixed
     */
    public function stat($bucketName, $fileKey)
    {
        $entry = $bucketName . ':' . $fileKey;
        $encodedEntry = \Wcs\url_safe_base64_encode($entry);


        $url = Config::WCS_MGR_URL . '/stat/' . $encodedEntry;

        $token = \Wcs\get_file_stat_token($bucketName, $fileKey);

        return $this->_get($url, $token);
    }

    /**
     * 列举资源
     * @param   $bucket
     * @param   $limit
     * @param   $prefix
     * @param   $mode
     * @param   $marker
     */
    public function lists(
        $bucket,
        $limit = 1000,
        $prefix = null,
        $mode = null,
        $marker = null
    ) {

        $path = '/list';
        $path .= "?bucket=$bucket";
        $path .= "&limit=$limit";
        if($prefix !== null) {
            $prefix = \Wcs\url_safe_base64_encode($prefix);
            $path.= "&prefix=$prefix";
        }
        if($mode !== null) {
            $path .= "&mode=$mode";
        }
        if($marker !== null) {
            $path .= "&marker=$marker";
        }

        $signingStr = $path . "\n";

        $token = \Wcs\get_src_manage_token($signingStr);
        $url = Config::WCS_MGR_URL . $path;

        $resp = $this->_get($url, $token);
        return $resp;

    }

     /**
     * 更新镜像资源
     * @param $fileKeys
     */
    public function updateMirrorSrc($bucket, $fileKeys) {
        $url = Config::WCS_MGR_URL . '/prefetch/';
        $separator = "|";
        $files = explode($separator, $fileKeys);
        $param = $bucket.":";
        foreach ($files as $index => $file) {
            $param .= Wcs\url_safe_base64_encode($file);
            if($index !== (sizeof($files) - 1)) {
                $param .= "|";
            }
        }
        $param = Wcs\url_safe_base64_encode($param);
        $signingStr = '/prefetch/' . $param . "\n";
        $token = Wcs\wcs_require_mac(null)->get_token($signingStr);
        $url .= $param;
        $resp = $this->_post($url, $token);

        return $resp;


    }

    /**
     * 获取音视频的元信息
     * @param   $key
     * */
    public  function  avInfo($host, $fileName) {
        $url = $host . '/' . $fileName;
        $params =  '?op=avinfo';
        $url .= $params;
        $resp = Wcs\http_get($url, null);

        return $resp->respBody;
    }

    /**
     * 获取音视频简单元信息
     * @param   $key
     * */
     public  function  avInfo2($host, $fileName) {
        $url = $host . '/' . $fileName;
        $params =  '?op=avinfo2';
        $url .= $params;
        $resp = Wcs\http_get($url, null);

        return $resp->respBody;
    }

    private function  _get($url, $token = null) {
        $headers = array("Authorization:$token");
        $resp = \Wcs\http_get($url, $headers);

        return $resp->respBody;
    }

    private function _post($url, $token = null) {
        $headers = array(
            "Authorization:$token",
            "Content-Length:0"
        );
        $resp = \Wcs\http_post($url, $headers, null);

        return $resp->respBody;
        //return $resp;
    }


}