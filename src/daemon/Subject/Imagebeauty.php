<?php
namespace mia\miagroup\Daemon\Subject;

use mia\miagroup\Service\Image as ImageService;
use mia\miagroup\Data\Subject\Subject as SubjectData;

/**
 * 美化图片
 */
class Imagebeauty extends \FD_Daemon {

    private $lastIdFile;
    private $tempFilePath;

    public function __construct() {
        $this->subjectData = new SubjectData();
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $this->tempFilePath = $runFilePath . '/subject/';
    }

    public function execute() {
        $this->beautyImg();exit;
    }

    public function beautyImg()
    {
        $image_service = new ImageService();
        $this->lastIdFile = $this->tempFilePath . 'subject_last_id';
        $succFile = '/home/xiekun/succ_img_id';
        $fp = fopen($succFile, 'a+');
        //读取上一次处理的id
        if (!file_exists($this->lastIdFile)) { //打开文件
            $lastId = 0;
            $fpLastIdFile = fopen($this->lastIdFile, 'w');
        } else {
            $fpLastIdFile = fopen($this->lastIdFile, 'r+');
        }
        if (!flock($fpLastIdFile, LOCK_EX | LOCK_NB)) { //加锁
            fclose($fpLastIdFile);
            return;
        }
        if (!isset($lastId)) { //获取last_id
            $lastId .= fread($fpLastIdFile, 1024);
            $lastId = intval($lastId);
        }
        $subjectData = new \mia\miagroup\Data\Subject\Subject();
        $data = $subjectData->query('SELECT id,ext_info FROM group_subjects WHERE ext_info != "" AND id > '.$lastId.' LIMIT 5');
        if (!empty($data)) {
            foreach ($data as $value) {
                $subject_id = $value['id'];
                $beauty = [];
                $beauty_image = [];
                if (isset($maxId)) { //获取最大event_id
                    $maxId = $subject_id > $maxId ? $subject_id : $maxId;
                } else {
                    $maxId = $subject_id;
                }
                // 日志记录
                fseek($fpLastIdFile, 0, SEEK_SET);
                ftruncate($fpLastIdFile, 0);
                fwrite($fpLastIdFile, $maxId);
                echo "current id:".$subject_id."\r\n";

                if(!empty($value['ext_info'])) {
                    $ext_info = json_decode($value['ext_info'], true);
                    $image_list = $ext_info['image'];
                    if(!empty($image_list) || count($image_list) > 0) {
                        foreach ($image_list as $image) {
                            if(!empty($image['url'])) {
                                $image_url = $image_service->beautyImage($image['url'])['data'];
                                if(!empty($image_url)){
                                    $beauty['url'] = $image_url;
                                    if(!empty($image['width'])) {
                                        $beauty['width'] = $image['width'];
                                    }
                                    if(!empty($image['height'])) {
                                        $beauty['height'] = $image['height'];
                                    }
                                }
                            }
                            $beauty_image[] = $beauty;
                        }
                    }
                }
                if(!empty($beauty_image)) {
                    $ext_info['beauty_image'] = $beauty_image;
                    $res = $subjectData->query('UPDATE group_subjects SET ext_info = \''.json_encode($ext_info) . '\' WHERE id = '.$subject_id);
                    if(!empty($res)) {
                        fwrite($fp, $subject_id."\n");
                        echo 'success id:'.$subject_id."\r\n";
                    }
                }
            }
        }
        flock($fpLastIdFile, LOCK_UN);
        fclose($fpLastIdFile);
    }

}