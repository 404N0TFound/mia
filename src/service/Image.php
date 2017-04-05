<?php
namespace mia\miagroup\Service;

use mia\miagroup\Util\ImageUtil;

class Image extends \mia\miagroup\Lib\Service
{

    public $image;
    public $img_server;
    public $imageConfig;
    public $imageTempUrl;


    public function __construct()
    {
        parent::__construct();
        $this->image = new ImageUtil();
        //下载图片临时存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $this->imageConfig = \F_Ice::$ins->workApp->config->get('busconf.image');
        $this->img_server = \F_Ice::$ins->workApp->config->get('app.url')['img_url'];
        $this->imageTempUrl = $runFilePath . '/image/';
    }

    /*
     * 图片裁剪
     * */
    public function cropImage($url, $width, $height, $x, $y)
    {
        if(empty($url)) {
            return $this->succ();
        }
        if(empty($x)) {
            $x = null;
        }
        if(empty($y)) {
            $y = null;
        }
        if(empty(strstr($url, 'http'))) {
            $url = substr($this->img_server, 0, strrpos($this->img_server, '/')) . $url;
        }
        $fileName = md5($url);
        $this->imageTempUrl = 'D:/tmpfile';
        if (!file_exists($this->imageTempUrl)) {
            mkdir($this->imageTempUrl, 0777, true);
        }
        $img_info = $this->image->downloadImage($url, $fileName, $this->imageTempUrl);
        if(empty($img_info)) {
            return $this->succ();
        }
        $temp_url = $img_info['saveDir'].'/'.$img_info['fileName'];
        $new_Dir = $img_info['saveDir'].'/crop';

        if (!file_exists($new_Dir)) {
            mkdir($new_Dir, 0777, true);
        }
        $newUrl = $new_Dir.'/'.$img_info['fileName'];
        $this->image->crop($temp_url, $newUrl, $width, $height,$x, $y);
        // 上传图片
        $post = $this->handleImgData($newUrl);
        $path = $this->image->uploadImage($post, $newUrl);
        return $this->succ($path);
    }

    /*
     * 上传图片加密封装
     * */
    public function handleImgData()
    {
        $post_data = [];
        $secret = $this->imageConfig['secret'];
        $params = $this->imageConfig['params'];
        $params['timestemp'] = time();
        $post_data['params'] = json_encode($params);
        ksort($params);
        $post_data['resource_id'] = $params['resource_id'];
        $param_str = http_build_query($params);
        $sign = md5($param_str.$secret);
        $post_data['sign'] = $sign;
        $post_data['timestemp'] = $params['timestemp'];
        $post_data['type'] = $params['type'];
        return $post_data;
    }

}