<?php
namespace Wcs\ImageProcess;

use Wcs;


class ImageInfo
{

    /**
     * 获取图片信息
     * @param   $key
     * */
    public function imgInfo($bucketName, $fileName) {
        $url = Wcs\build_public_url($bucketName, $fileName);
        $params = '?op=imageInfo';
        $url .= $params;
        $resp = Wcs\http_get($url, null);

        return $resp->respBody;
    }

    /**
     * 获取图片EXIF信息
     * @param   $key
     * */
    public function imgEXIF($bucketName, $fileName) {
        $url = Wcs\build_public_url($bucketName, $fileName);
        $params = '?op=exif';
        $url .= $params;
        $resp = Wcs\http_get($url, null);

        return $resp->respBody;
    }


} 