<?php
namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Data\Subject\Subject as SubjectData;
use mia\miagroup\Data\Album\AlbumArticle as AlbumArticleData;

/**
 * 帖子相关-临时脚本
 */
 
class Subject extends \FD_Daemon {

    public function __construct() {
        $this->subjectData = new SubjectData();
        $this->albumArticleData = new AlbumArticleData();
    }

    public function execute() {
        $this->editSubjectImg();exit;
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

        $data = file('/home/xiekun/subject_image');
        //$data = file('D:/tmpfile/test.txt');
        $i = 1;
        set_time_limit(0);
        // 日志
        $editFile = '/home/xiekun/return_article_img';
        $fp = fopen($editFile, 'a+');
        foreach ($data as $v) {
            list($id, $url_json) = explode(" ", $v);
            if ($i % 100 == 0) {
                sleep(1);
            }
            $i ++;
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
                        $flag = $this->albumArticleData->updateArticleImg($id, $update_data);
                        if($flag) {
                            echo 'success:', $id, "\n";
                            fwrite($fp, $id.'_'.$update_data,"\n");
                        }
                    }
                }
            }
        }
    }
}