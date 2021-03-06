<?php
namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Data\Subject\Subject as SubjectData;
use mia\miagroup\Data\Album\AlbumArticle;

/**
 * 帖子相关-临时脚本
 */
 
class Subject extends \FD_Daemon {

    public function __construct() {
        $this->subjectData = new SubjectData();
    }

    public function execute() {
        $function_name = $this->request->argv[0];
        $this->$function_name();
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
    
    public function fix_praise_subject_uid() {
        $praise_data = new \mia\miagroup\Data\Praise\SubjectPraise();
        $sql = 'select distinct(subject_id) from group_subject_praises where subject_uid = 0 limit 1000';
        $data = $praise_data->query($sql);
        if (empty($data)) {
            return;
        }
        $subject_ids = array_column($data, 'subject_id');
        $sql = 'select id, user_id from group_subjects where id in (' . implode(',', $subject_ids) .')';
        $data = $praise_data->query($sql);
        if (empty($data)) {
            return ;
        }
        foreach ($data as $v) {
            $update_sql = "update group_subject_praises set subject_uid = {$v['user_id']} where subject_id = {$v['id']}";
            $praise_data->query($update_sql);
        }
    }
    
    public function fix_comment_subject_uid() {
        $praise_data = new \mia\miagroup\Data\Praise\SubjectPraise();
        $sql = 'select distinct(subject_id) from group_subject_comment where subject_uid = 0 limit 1000';
        $data = $praise_data->query($sql);
        if (empty($data)) {
            return;
        }
        $subject_ids = array_column($data, 'subject_id');
        $sql = 'select id, user_id from group_subjects where id in (' . implode(',', $subject_ids) .')';
        $data = $praise_data->query($sql);
        if (empty($data)) {
            return ;
        }
        foreach ($data as $v) {
            $update_sql = "update group_subject_comment set subject_uid = {$v['user_id']} where subject_id = {$v['id']}";
            $praise_data->query($update_sql);
        }
    }
    
    public function change_subject_user() {
        $change_list = file('/home/hanxiang/subject_user_change');
        foreach ($change_list as $v) {
            $v = trim($v);
            list($origin_user_id, $new_user_id) = explode("\t", $v);
            //更新帖子表
            $sql = "update group_subjects set user_id = $new_user_id where user_id = $origin_user_id ;";
            echo $sql . "\n";
            //$this->subjectData->query($sql);
            //更新口碑表
            $sql = "update koubei set user_id = $new_user_id where user_id = $origin_user_id ;";
            echo $sql . "\n";
            //$this->subjectData->query($sql);
            //更新评论表
            $sql = "update group_subject_comment set user_id = $new_user_id where user_id = $origin_user_id ;";
            echo $sql . "\n";
            //$this->subjectData->query($sql);
            //更新赞表
            $sql = "update group_subject_praises set user_id = $new_user_id where user_id = $origin_user_id ;";
            echo $sql . "\n";
            //$this->subjectData->query($sql);
            //更新标签表
            $sql = "update group_subject_label_relation set user_id = $new_user_id where user_id = $origin_user_id ;";
            echo $sql . "\n";
            //$this->subjectData->query($sql);
            //更新活动表
            $sql = "update group_subject_active_relation set user_id = $new_user_id where user_id = $origin_user_id ;";
            echo $sql . "\n";
            //$this->subjectData->query($sql);
            //更新长文表
            $sql = "update group_subject_blog_info set user_id = $new_user_id where user_id = $origin_user_id ;";
            echo $sql . "\n";
            //$this->subjectData->query($sql);
            //更新视频表
            $sql = "update group_subject_video set user_id = $new_user_id where user_id = $origin_user_id ;";
            echo $sql . "\n";
            //$this->subjectData->query($sql);
        }
    }
    
    public function upload_img() {
        $file_path = $this->request->argv[1];
        $img_util = new \mia\miagroup\Util\ImageUtil();
        $img_service = new \mia\miagroup\Service\Image();
        $data = $img_service->handleImgData()['data'];
        $remote_url = 'http://uploads.miyabaobei.com/app_upload.php';
        
        // 上传图片
        if (class_exists('\CURLFile')) {
            $data['Filedata'] = new \CURLFile(realpath($file_path), 'image.jpg');
        } else {
            $data['Filedata'] = '@' . realpath($file_path);
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
        var_dump($res);
    }
}