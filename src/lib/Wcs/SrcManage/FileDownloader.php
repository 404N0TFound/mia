<?php
namespace  Wcs\SrcManage;

use Wcs;
use Wcs\Config;
use Wcs\Http\Response;

class FileDownloader
{
    /**
     * @var int 下载超时时间，默认不限时间
     */
    private static $DOWNLOAD_TIMEOUT = 0;

    /**
     * 命名空间
     * @var $bucketName
     */
    public $bucketName;

    /**
     * 源文件
     * @var $fileName
     */
    public $fileName;

    /**
     * 保存的文件
     * @var $localFile
     */
    public $localFile;


    /**
     * FileDownloader constructor.
     * @param $bucketName
     * @param $fileName
     * @param null $localFile
     */
    public function __construct($bucketName, $fileName, $localFile = null)
    {
        $this->bucketName = $bucketName;
        $this->fileName = $fileName;
        if(empty($localFile)) {
           $this->localFile = $fileName;
        }
        else {
            $this->localFile = $localFile;
        }
    }


    /**
     * 正常下载
     * @param $bucketName
     * @param $fileName
     * @param null $localFile
     * @return mixed
     */
    public function download() {

        $url = Config::WCS_GET_URL."/".$this->fileName;
        $resp = $this->_download($url, $this->localFile);

        return $resp->message;

    }

    /**
     * @param $url
     * @param $localFile
     * @return Response
     */
    public function _download($url, $localFile) {

        $options = array(
            CURLOPT_CONNECTTIMEOUT => self::$DOWNLOAD_TIMEOUT,
        );

        $resp = Wcs\http_get($url, null, $options);


        //检查文件完整性，保存下载文件
        if((int)($resp->code / 100) == 2) {

            //检查下载文件的完整性,获取源文件大小
            $client = new FileManager();
            $result = $client->stat($this->bucketName, $this->fileName);
            $result = json_decode($result, true);
            $fileSize = $result['fsize'];
            $localSize = strlen($resp->respBody);

            //文件完整，保存文件，否则报错退出
            if($fileSize == $localSize) {
                //保存文件
                $file = fopen($localFile, "w");
                fwrite($file, $resp->respBody, strlen($resp->respBody));
                fclose($file);
                $resp->message = "文件下载完成！";
            }
            else {
                die("文件完整性验证失败！");
            }
        }
        else {
            $resp->message = $resp->respBody;
        }

        return $resp;

    }

    /**
     * 支持断点下载
     * @param $bucketName
     * @param $fileName
     * @param null $localFile 不指定，默认为空
     * @return string|Wcs\Http\Response
     */
    public function resumeDownload()
    {
        $url = Config::WCS_GET_URL."/".$this->fileName;
        $resp = $this->_resumeDownload($url, $this->bucketName, $this->fileName, $this->localFile);

        return $resp->message;
    }

    /**
     * @param $url
     * @param $bucketName
     * @param $fileName
     * @param $localFile
     * @return string|Response
     */
    private function _resumeDownload($url, $bucketName, $fileName, $localFile)
    {

        //判断文件是否未下载完成，如果存在，那么就执行断点下载
        if (file_exists($localFile)) {

            //判断文件的完整性
            $client = new FileManager();
            $result = $client->stat($bucketName, $fileName);
            $result = json_decode($result, true);
            $fileSize = $result['fsize'];
            $localSize = filesize($localFile);

            //文件已经存在的情况
            if($localSize == $fileSize) {
                $resp = new Wcs\Http\Response();
                $resp->message = '文件已经存在！';
                return $resp;
            }

            $range = 'bytes=' . $localSize . '-' . $fileSize;
            $headers = array(
                "Range:$range"
            );
        }
        else {
            $headers = null;
        }

        $file = fopen($localFile, "a");

        $options = array(
            //不返回头部数据
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => self::$DOWNLOAD_TIMEOUT,
            CURLOPT_FILE => $file
        );

        $resp = Wcs\http_get($url, $headers, $options);
        fclose($file);

        //下载失败，删除文件
        if((int)($resp->code / 100) !== 2) {
            $file = fopen($localFile, "r");
            $result = fread($file,filesize($localFile));
            $responseArray = explode("\r\n\r\n", $result);
            $resp->message = $responseArray[0];

            //下载格式出错，删除文件
            unlink($localFile);

            fclose($file);
        }
        else {
                $resp->message = '文件下载完成！';
        }

        return $resp;
    }
}