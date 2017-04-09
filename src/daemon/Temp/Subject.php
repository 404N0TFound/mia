<?php
namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Data\Subject\Subject as SubjectData;
use mia\miagroup\Data\Album\AlbumArticle;
use mia\miagroup\Service\Image as ImageService;

/**
 * 帖子相关-临时脚本
 */
 
class Subject extends \FD_Daemon {

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
    
    /**
     * 删除帖子
     */
    public function delete_subject() {
        $file_path = '/home/hanxiang/all_share_baby_ids.txt';
        $data = file($file_path);
        $i = 0;
        foreach ($data as $v) {
            $i ++;
            $subject_ids[] = trim($v);
            if ($i % 1000 == 0) {
                $this->subjectData->deleteSubjects($subject_ids, 0);
                $subject_ids = [];
                sleep(1);
            }
        }
    }
    
    /**
     * 帖子关联商品，并同步口碑
     */
    public function subject_relate_item() {
        $file_path = '/home/hanxiang/being_related_subjects';
        $data = file($file_path);
        $i = 0;
        $pointService = new \mia\miagroup\Service\PointTags();
        $koubeiService = new \mia\miagroup\Service\Koubei();
        foreach ($data as $v) {
            $i ++;
            $v = trim($v);
            list($subject_id, $item_id) = explode("\t", $v);
            $pointService->saveSubjectTags($subject_id, array('item_id' => $item_id));
            $koubeiService->setSubjectToKoubei($subject_id, $item_id);
            if ($i % 200 == 0) {
                sleep(1);
            }
        }
    }

    /*
     * 修复头条视频首图宽高
     * */
    public function editSubjectImg() {
        $editFile = '/home/xiekun/return_article_img';
        $fp = fopen($editFile, 'a+');
        $albumArticleData = new AlbumArticle();
        //$handle = @fopen("/home/xiekun/subject_image", "r");
        $handle = @fopen("D:/tmpfile/test.txt", "r");
        if ($handle) {
            while (!feof($handle)) {
                $line = fgets($handle, 40960);
                list($id, $url_json) = explode(" ", $line);
                if(!empty($url_json)) {
                    $url_data = json_decode($url_json, true);
                    if($url_data['width'] == 702  &&  $url_data['height'] == 204) {
                        $img_url = 'https://video1.miyabaobei.com/'.$url_data['url'].'&imageInfo';
                        $img_data = file_get_contents($img_url);
                        $img_info = json_decode($img_data, true);
                        $width = $img_info['width'];
                        $height = $img_info['height'];
                        $url_data['width'] = $width;
                        $url_data['height'] = $height;
                        if(!empty($url_data)) {
                            $update_data = json_encode($url_data);
                        }
                        if(!empty($id) && !empty($update_data)) {
                            $flag = $albumArticleData->query("update group_article set cover_image = '".$update_data."' where id = ".$id, \DB_Query::RS_ARRAY);
                            if($flag) {
                                echo 'success:', $id, "\n";
                                fwrite($fp, $id.'_'.$update_data,"\n");
                            }
                        }
                    }
                }
            }
            fclose($handle);
        }
    }

    /*
     * 美化图片
     * */
    public function beautyImg()
    {
        $image_service = new ImageService();
        $this->lastIdFile = $this->tempFilePath . 'subject_last_id';
        //$this->lastIdFile = 'D:/tmpfile/subject_last_id';
        $succFile = '/home/xiekun/succ_img_id';
        //$succFile = 'D:/tmpfile/succ_img_id';
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
                        echo 'success id:'.$subject_id;
                    }
                }
            }
        }
        flock($fpLastIdFile, LOCK_UN);
        fclose($fpLastIdFile);
    }
}