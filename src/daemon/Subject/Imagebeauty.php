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
        $this->batchBeautyByIds();exit;
        $this->beautyImg();
    }

    public function beautyImg()
    {
        $image_service = new ImageService();
        $this->lastIdFile = $this->tempFilePath . 'beauty_last_id';
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
        $data = $subjectData->query('SELECT id,ext_info FROM group_subjects WHERE ext_info != "" AND image_url != "" AND id < '.$lastId.' order by id desc LIMIT 100');
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
                    if(!empty($ext_info['beauty_image'])) {
                        continue;
                    }
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


    /*
     * 近3个月帖子美化图片处理
     * */
    public function batchBeautyByIds()
    {
        $image_service = new ImageService();
        $succFile = '/home/xiekun/tmp_succes_image_id';
        $fp = fopen($succFile, 'a+');
        $handle = @fopen("/tmp/beauty_subject_id", "r");
        //$handle = @fopen("D:/tmpfile/t1", "r");
        $this->lastIdFile = $this->tempFilePath . 'tmp_beauty_id';
        //$this->lastIdFile = 'D:/tmpfile/tmp_beauty_id';
        if (!file_exists($this->lastIdFile)) { //打开文件
            $fpLastIdFile = fopen($this->lastIdFile, 'w');
        } else {
            $fpLastIdFile = fopen($this->lastIdFile, 'r+');
        }
        if (!flock($fpLastIdFile, LOCK_EX | LOCK_NB)) { //加锁
            fclose($fpLastIdFile);
            return;
        }
        $subject_file_id = fgets($fpLastIdFile, 2048);
        if ($handle) {
            while (!feof($handle)) {
                $subject_id = fgets($handle, 2048);
                if(intval($subject_id) < intval($subject_file_id)) {
                    continue;
                }
                fseek($fpLastIdFile, 0, SEEK_SET);
                ftruncate($fpLastIdFile, 0);
                fwrite($fpLastIdFile, $subject_id);
                $subjectData = new \mia\miagroup\Data\Subject\Subject();
                $data = $subjectData->query('SELECT ext_info FROM group_subjects WHERE ext_info != "" AND image_url != "" AND id = '.$subject_id);
                if(empty($data)) {
                    continue;
                }
                $beauty_image = [];
                $ext_info = $data[0]['ext_info'];
                $handle_info = json_decode($ext_info, true);
                if(!empty($handle_info['beauty_image']) || empty($handle_info['image'])) {
                    continue;
                }

                foreach ($handle_info['image'] as $image) {
                    if(empty($image['url'])) {
                        continue;
                    }
                    $image_url = $image_service->beautyImage($image['url'])['data'];

                    if(empty($image_url)){
                        continue;
                    }

                    $beauty['url'] = $image_url;

                    if(!empty($image['width'])) {
                        $beauty['width'] = $image['width'];
                    }
                    if(!empty($image['height'])) {
                        $beauty['height'] = $image['height'];
                    }
                    $beauty_image[] = $beauty;

                }
                if(!empty($beauty_image)) {
                    $handle_info['beauty_image'] = $beauty_image;
                    $res = $subjectData->query('UPDATE group_subjects SET ext_info = \''.json_encode($handle_info) . '\' WHERE id = '.$subject_id);
                    if(!empty($res)) {
                        fwrite($fp, $subject_id);
                        echo 'success id:'.$subject_id;
                        unset($beauty_image);
                    }
                }
            }
        }
    }
}