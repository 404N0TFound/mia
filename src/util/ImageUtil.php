<?php
namespace mia\miagroup\Util;

use Intervention\Image\ImageManager;

class ImageUtil
{

    public  $config;
    private $imgSource;

    public function __construct()
    {
        $this->imgSource = new ImageManager();
        $this->config = F_Ice::$ins->workApp->config->get('busconf.image');
    }


    /*
     * 图片大小
     * Returns the size of the image file in bytes
     * */
    public function fileSize($path)
    {
        $img = $this->imgSource->make($path);
        $size = $img->filesize();
        return $size;
    }

    /*
     * 调整图片
     * type:normal
     * */
    public function reSize($path, $newPath, $width, $height)
    {
        $img = $this->imgSource->make($path);
        $img->resize($width, $height);
        $img->save($newPath);
    }

    /*
     * 调整图片
     * type:auto
     * */
    public function reSizeAuto($path, $newPath, $width, $height)
    {
        $img = $this->imgSource->make($path);
        $img->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->save($newPath);
    }


    /*
     * 图片裁剪
     * */
    public function crop($path, $newPath, $width, $height, $x, $y)
    {
        $img = $this->imgSource->make($path);
        $img->crop($width, $height, $x, $y);
        $img->save($newPath);
    }

    /*
     * 图片亮度
     * */
    public function brightNess($path, $newPath, $level)
    {
        $img = $this->imgSource->make($path);
        $img->brightness($level);
        $img->save($newPath);
    }

    /*
     * 图片锐化
     * */
    public function sharpen($path, $newPath, $level)
    {
        $img = $this->imgSource->make($path);
        $img->sharpen($level);
        $img->save($newPath);
    }

    /*
     * 图片上传
     * */
    public function uploadImage()
    {

    }

    /*
     * 图片下载
     * */
    public function downloadImage()
    {

    }

}