<?php
namespace mia\miagroup\Service;

use mia\miagroup\Util\ImageUtil;

class Image extends \mia\miagroup\Lib\Service
{

    public $image;

    public function __construct()
    {
        parent::__construct();
        $this->image = new ImageUtil();
        $this->config = F_Ice::$ins->workApp->config->get('busconf.image');
    }

    /*
     * 图片裁剪
     * */
    public function cropImage($path, $width, $height, $x, $y)
    {
        if(empty($path)) {
            return $this->succ();
        }
        if(!empty($width) && !empty($height)) {
            $newPath = '';
            $this->image->crop($path, $newPath, $width, $height, $x, $y);
        }
    }

}