<?php
namespace mia\miagroup\Daemon\Subject;

use mia\miagroup\Service\Image as ImageService;
use mia\miagroup\Data\Subject\Subject as SubjectData;

/**
 * 帖子图片美化
 */
class Imagebeauty extends \FD_Daemon
{
    private $lastIdFile;
    private $tempFilePath;

    public function __construct()
    {
        $this->subjectData = new SubjectData();
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $this->tempFilePath = $runFilePath . '/subject/';
    }

    public function execute()
    {
        $this->deltaImportSubjectImage();exit;
        $this->fullImportSubjectImage();
    }

    /*
     * 全量美化图片
     * */
    public function fullImportSubjectImage()
    {
        ini_set('gd.jpeg_ignore_warning', 1);
        $image_service = new ImageService();
        $succFile = '/home/xiekun/succ_img_id';
        $fp = fopen($succFile, 'a+');
        $this->lastIdFile = $this->tempFilePath . 'beauty_dump_last_id';
        //$this->lastIdFile = 'd:/tmpfile/beauty_dump_last_id';
        $subjectData = new \mia\miagroup\Data\Subject\Subject();
        //读取上一次处理的id
        if (!file_exists($this->lastIdFile)) { //打开文件
            $data = $subjectData->query('SELECT max(id) as id FROM group_subjects WHERE ext_info != "" AND image_url != "" AND status = 1');
            $lastId = $data[0]['id'] +1;
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
        $data = $subjectData->query('SELECT id,ext_info FROM group_subjects WHERE ext_info != "" AND status = 1 AND image_url != "" AND id < ' . $lastId . ' LIMIT 100');
        if (!empty($data)) {
            foreach ($data as $value) {
                $subject_id = $value['id'];
                $beauty = [];
                $beauty_image = [];
                // 日志记录
                fseek($fpLastIdFile, 0, SEEK_SET);
                ftruncate($fpLastIdFile, 0);
                fwrite($fpLastIdFile, $subject_id);

                if (!empty($value['ext_info'])) {
                    $ext_info = json_decode($value['ext_info'], true);
                    if (!empty($ext_info['beauty_image'])) {
                        continue;
                    }
                    $image_list = $ext_info['image'];
                    if (!empty($image_list) || count($image_list) > 0) {
                        foreach ($image_list as $image) {
                            if (!empty($image['url'])) {
                                $image_url = $image_service->beautyImage($image['url'])['data'];
                                if (!empty($image_url)) {
                                    $beauty['url'] = $image_url;
                                    if (!empty($image['width'])) {
                                        $beauty['width'] = $image['width'];
                                    }
                                    if (!empty($image['height'])) {
                                        $beauty['height'] = $image['height'];
                                    }
                                }
                            }
                            $beauty_image[] = $beauty;
                        }
                    }
                }
                if (!empty($beauty_image)) {
                    $ext_info['beauty_image'] = $beauty_image;
                    $res = $subjectData->query('UPDATE group_subjects SET ext_info = \'' . json_encode($ext_info) . '\' WHERE id = ' . $subject_id);
                    if (!empty($res)) {
                        fwrite($fp, $subject_id . "\n");
                        echo 'success id:' . $subject_id . "\r\n";
                    }
                }
            }
        }
        flock($fpLastIdFile, LOCK_UN);
        fclose($fpLastIdFile);
    }

    /*
     * 增量美化图片
     * */
    public function deltaImportSubjectImage()
    {
        ini_set('gd.jpeg_ignore_warning', 1);
        $image_service = new ImageService();
        $subjectData = new \mia\miagroup\Data\Subject\Subject();
        $date = date("Y-m-d",time()-86400);
        $startTime = $date . " 00:00:00";
        $endTime = $date . " 23:59:59";
        $data = $subjectData->query('SELECT id,ext_info FROM group_subjects WHERE ext_info != "" AND image_url != "" AND status = 1 AND created >= "' . $startTime . '" AND created <= "'.$endTime. '"');
        if (!empty($data)) {
            foreach ($data as $value) {
                $subject_id = $value['id'];
                $beauty = [];
                $beauty_image = [];

                if (!empty($value['ext_info'])) {
                    $ext_info = json_decode($value['ext_info'], true);
                    if (!empty($ext_info['beauty_image'])) {
                        continue;
                    }
                    $image_list = $ext_info['image'];
                    if (!empty($image_list) && count($image_list) > 0) {
                        foreach ($image_list as $image) {
                            if (!empty($image['url'])) {
                                $image_url = $image_service->beautyImage($image['url'])['data'];
                                if (!empty($image_url)) {
                                    $beauty['url'] = $image_url;
                                    if (!empty($image['width'])) {
                                        $beauty['width'] = $image['width'];
                                    }
                                    if (!empty($image['height'])) {
                                        $beauty['height'] = $image['height'];
                                    }
                                }
                            }
                            $beauty_image[] = $beauty;
                        }
                    }
                }
                if (!empty($beauty_image)) {
                    $ext_info['beauty_image'] = $beauty_image;
                    $subjectData->query('UPDATE group_subjects SET ext_info = \'' . json_encode($ext_info) . '\' WHERE id = ' . $subject_id);
                }
            }
        }
    }



    /*
     * 批量处理美化图片
     * */
    public function batchBeautyByIds()
    {

        ini_set('gd.jpeg_ignore_warning', 1);
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
                if (intval($subject_id) < intval($subject_file_id)) {
                    continue;
                }
                echo 'current id:' . $subject_id;
                fseek($fpLastIdFile, 0, SEEK_SET);
                ftruncate($fpLastIdFile, 0);
                fwrite($fpLastIdFile, $subject_id);
                $subjectData = new \mia\miagroup\Data\Subject\Subject();
                $data = $subjectData->query('SELECT ext_info FROM group_subjects WHERE ext_info != "" AND status = 1 AND image_url != "" AND id = ' . $subject_id);
                if (!empty($data)) {
                    foreach ($data as $value) {
                        $beauty = [];
                        $beauty_image = [];
                        if (!empty($value['ext_info'])) {
                            $ext_info = json_decode($value['ext_info'], true);
                            if (!empty($ext_info['beauty_image'])) {
                                continue;
                            }
                            $image_list = $ext_info['image'];
                            if (!empty($image_list) || count($image_list) > 0) {
                                foreach ($image_list as $image) {
                                    if (!empty($image['url'])) {
                                        $image_url = $image_service->beautyImage($image['url'])['data'];
                                        if (!empty($image_url)) {
                                            $beauty['url'] = $image_url;
                                            if (!empty($image['width'])) {
                                                $beauty['width'] = $image['width'];
                                            }
                                            if (!empty($image['height'])) {
                                                $beauty['height'] = $image['height'];
                                            }
                                        }
                                    }
                                    $beauty_image[] = $beauty;
                                }
                            }
                        }
                        if (!empty($beauty_image)) {
                            $ext_info['beauty_image'] = $beauty_image;
                            $res = $subjectData->query('UPDATE group_subjects SET ext_info = \'' . json_encode($ext_info) . '\' WHERE id = ' . $subject_id);
                            if (!empty($res)) {
                                fwrite($fp, $subject_id);
                                echo 'success id:' . $subject_id;
                            }
                        }
                    }
                }
                flock($fpLastIdFile, LOCK_UN);
                fclose($fpLastIdFile);
            }
        }
    }
}