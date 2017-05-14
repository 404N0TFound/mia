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
    public function sharpenImg($path, $newPath, $level)
    {
        $img = $this->imgSource->make($path);
        $img->sharpen($level);
        $img->save($newPath);
    }

    /*
     * 图片对比
     * */
    public function contrastImg($path, $newPath, $level)
    {
        $img = $this->imgSource->make($path);
        $img->contrast($level);
        $img->save($newPath);
    }

    /*
     * 图片上传
     * */
    public function uploadImage($data, $imgUrl)
    {

        $config = \F_Ice::$ins->workApp->config->get('busconf.image');
        $remote_url = $config['remote_url'];

        // 上传图片
        if (class_exists('\CURLFile')) {
            $data['Filedata'] = new \CURLFile(realpath($imgUrl), 'image.jpg');
        } else {
            $data['Filedata'] = '@' . realpath($imgUrl);
        }

        $curl= curl_init ();
        curl_setopt ( $curl, CURLOPT_URL, $remote_url);
        curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, FALSE );

        //发送post数据
        curl_setopt ( $curl, CURLOPT_POST, 1 );
        curl_setopt ( $curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
        $output= curl_exec ( $curl);
        curl_close ( $curl);
        $res = json_decode($output, true);
        if($res['code'] != 200) return '';
        return $res['content'];
    }


    /**
     * 下载远程图片到本地
     *
     * @param string $url 远程文件地址
     * @param string $filenNme 保存后的文件名（为空时则为随机生成的文件名，否则为原文件名）
     * @param array $fileType 允许的文件类型
     * @param string $dirName 文件保存的路径（路径其余部分根据时间系统自动生成）
     * @param int $type 远程获取文件的方式
     * @return  string 返回文件名、文件的保存路径
     * @author 52php.cnblogs.com
     */
    function downloadImage($url, $fileName = '', $dirName, $fileType = array('jpg', 'gif', 'png'), $type = 1)
    {
        if (empty($url)) {
            return '';
        }
        // 获取文件原文件名
        //$defaultFileName = basename($url);

        // 获取文件类型
        $suffix = substr(strrchr($url, '.'), 1);
        if (!in_array($suffix, $fileType)) {
            return false;
        }
        // 固定后缀
        $suffix = 'jpg';
        // 设置保存后的文件名
        //$fileName = $fileName == '' ? time() . rand(0, 9) . '.' . $suffix : $defaultFileName;
        $fileName = $fileName . '.' . $suffix;

        // 获取远程文件资源
        if ($type) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $file = curl_exec($ch);
            curl_close($ch);
        } else {
            ob_start();
            readfile($url);
            $file = ob_get_contents();
            ob_end_clean();
        }
        // 设置文件保存路径
        $dirName = $dirName . '/' . date('Ym', time());
        if (!file_exists($dirName)) {
            mkdir($dirName, 0777, true);
        }

        // 保存文件
        $res = fopen($dirName . '/' . $fileName, 'a');
        fwrite($res, $file);
        fclose($res);

        $img_info['fileName'] = $fileName;
        $img_info['saveDir'] = $dirName;

        return $img_info;
    }

    /*
    * 图片美化
    * */
    public function beauty($path, $newPath)
    {
        $img = $this->imgSource->make($path);
        // 亮度 10
        $img->brightness(5);
        // 对比度 15
        $img->contrast(5);
        // 锐化 30
        $img->sharpen(10);
        $img->save($newPath);
    }
}