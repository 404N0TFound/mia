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
        $this->imageTempUrl = $runFilePath . '/image';
    }

    /*
     * 图片裁剪
     * width 必选
     * height 必选
     * x 可选
     * y 可选
     * */
    public function cropImage($url, $width, $height, $x, $y)
    {
        if(empty($url) || empty($width) || empty($height)) {
            return $this->succ();
        }
        // 图片下载
        $img_info = $this->downLoad($url)['data'];
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
        $post = $this->handleImgData()['data'];
        $path = $this->image->uploadImage($post, $newUrl);
        $return_url = substr($this->img_server, 0, strrpos($this->img_server, '/')) . $path;
        return $this->succ($return_url);
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
        return $this->succ($post_data);
    }

    /*
     * 图片下载
     * */
    public function downLoad($url)
    {
        if(empty(strstr($url, 'http'))) {
            $url = substr($this->img_server, 0, strrpos($this->img_server, '/')) . $url;
        }
        // 图片名唯一
        $fileName = md5($url.time());
        //$this->imageTempUrl = 'D:/tmpfile/image';
        if (!file_exists($this->imageTempUrl)) {
            mkdir($this->imageTempUrl, 0777, true);
        }
        $img_info = $this->image->downloadImage($url, $fileName, $this->imageTempUrl);
        return $this->succ($img_info);
    }

    /*
    * 图片美化
    * */
    public function beautyImage($url)
    {
        if(empty($url)) {
            return $this->succ();
        }
        $img_info = $this->downLoad($url)['data'];
        if(empty($img_info)) {
            return $this->succ();
        }
        //临时图片保存路径
        $temp_url = $img_info['saveDir'].'/'.$img_info['fileName'];
        $new_Dir = $this->imageTempUrl.'/beauty';
        if (!file_exists($new_Dir)) {
            mkdir($new_Dir, 0777, true);
        }
        $newUrl = $new_Dir.'/'.$img_info['fileName'];
        $this->image->beauty($temp_url, $newUrl);
        // 上传图片
        $post = $this->handleImgData()['data'];
        $path = $this->image->uploadImage($post, $newUrl);
        return $this->succ($path);
    }
}