<?php
namespace mia\miagroup\Daemon\Temp;

class Image extends \FD_Daemon
{
    private $imageUtil;
    private $image_compress_record_file;
    private $image_compress_record;
    
    public function __construct()
    {
        $this->imageUtil = new \mia\miagroup\Util\ImageUtil();
        $this->image_compress_record_file = '/opt/hanxiang/image_compress_record';
    }

    public function execute() {
        $function_name = $this->request->argv[0];
        $this->$function_name();
        if ($function_name == 'tongling_img_compress') {
            $data = file($this->image_compress_record_file);
            if (!empty($data)) {
                foreach ($data as $v) {
                    $v = trim($v);
                    $this->image_compress_record[$v] = 1;
                }
            }
            
        }
    }
    
    public function tongling_img_compress($root_dir = '', $output_dir = '') {
        if (empty($root_dir)) {
            $root_dir = '/opt/hanxiang/同龄图片';
        }
        if (empty($output_dir)) {
            $output_dir = '/opt/hanxiang/同龄图片compress';
        }
        $this->mk_dir($output_dir);
        $handle = opendir($root_dir);
        if ($handle) {
            while(($file = readdir($handle)) !== false) {
                if ($file != '.' && $file != '..') {
                    $cur_path = $root_dir . DIRECTORY_SEPARATOR . $file;
                    $cur_out_path = $output_dir . DIRECTORY_SEPARATOR . $file;
                    if (is_dir($cur_path)) {
                        $this->tongling_img_compress($cur_path, $cur_out_path);
                    } else {
                        $md5_path = md5($cur_path);
                        if (array_key_exists($md5_path, $this->image_compress_record)) {
                            $this->imageUtil->compress($cur_path, $cur_out_path);
                            file_put_contents($this->image_compress_record_file, $md5_path . "\n", FILE_APPEND);
                            echo $cur_path, ' ', $cur_out_path, "\n";
                        }
                    }
                }
            }
        } 
    }

    /**
     * 检测路径是否存在并自动生成不存在的文件夹
     */
    private function mk_dir($path) {
        if(is_dir($path)) return true;
        if(empty($path)) return false;
        $path = rtrim($path, '/');
        $bpath = dirname($path);
        if(!is_dir($bpath)) {
            if(!$this->mk_dir($bpath)) return false;
        }
        if(!@chdir($bpath)) return false;
        if(!@mkdir(basename($path))) return false;
        return true;
    }

}