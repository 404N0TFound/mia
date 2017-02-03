<?php
namespace mia\miagroup\Daemon\Subject;

/**
 * 导出帖子数据
 */
class Subjectdump extends \FD_Daemon {

    private $lastIdFile;
    private $dumpSubjectFile;
    private $dumpUserFile;

    public function __construct() {
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $tempFilePath = $runFilePath . '/subject/';
        $this->lastIdFile = $tempFilePath . 'subject_dump_last_id';
        $this->dumpSubjectFile = $tempFilePath . 'dump_subject_file_do_not_delete';
        $this->dumpUserFile = $tempFilePath . 'dump_user_file_do_not_delete';
    }

    public function execute() {
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
        if ($lastId <= 0) {
            return ;
        }
        
        //拉取新发布的帖子
        $subjectData = new \mia\miagroup\Data\Subject\Subject();
        $where = [];
        $where[] = [':gt','id', $lastId];
        $where[] = ['source', [1, 2]];
        $where[] = ['status', 1];
        $data = $subjectData->getRows($where, 'id, user_id', 1000);
        if (empty($data)) {
            return ;
        }
        $subjectIds = array();
        $userIds = array();
        foreach ($data as $value) {
            if (!empty($value)) {
                $subjectIds[] = $value['id'];
                $userIds[] = $value['user_id'];
            }
        }
        $subjectService = new \mia\miagroup\Service\Subject();
        $userService = new \mia\miagroup\Service\User();
        $subjectInfos = $subjectService->getBatchSubjectInfos($subjectIds, 0, array('count', 'group_labels', 'item', 'album'))['data'];
        $where = [];
        $where[] = [':le','id', $lastId];
        $where[] = ['source', [1, 2]];
        $where[] = ['status', 1];
        $where[] = ['user_id', $userIds];
        $existUids = $subjectData->getRows($where, 'distinct(user_id)');
        $existUids = array_column($existUids, 'user_id');
        $userIds = array_diff($userIds, $existUids);
        $userInfos = $userService->getUserInfoByUids($userIds, 0, array('count'))['data'];
        foreach ($data as $value) {
            if (isset($maxId)) { //获取最大event_id
                $maxId = $value['id'] > $maxId ? $value['id'] : $maxId;
            } else {
                $maxId = $value['id'];
            }
            if (!isset($subjectInfos[$value['id']])) {
                continue;
            }
            $subject = $subjectInfos[$value['id']];
            
            $dumpdata = array();
            //帖子ID
            $dumpdata['id'] = $subject['id'];
            //用户ID
            $dumpdata['user_id'] = $subject['user_id'];
            //帖子类型
            if (intval($subject['koubei_id']) > 0) {
                $dumpdata['subject_type'] = 'koubei';
            } else if (!empty($subject['album_article'])) {
                $dumpdata['subject_type'] = 'album_article';
            } else if (!empty($subject['video_info'])) {
                $dumpdata['subject_type'] = 'video';
            } else {
                $dumpdata['subject_type'] = 'normal';
            }
            //帖子图片张数
            $dumpdata['image_count'] = count($subject['image_infos']);
            //帖子内容长度
            $dumpdata['text_lenth'] = mb_strlen($subject['text'], 'utf8');
            //帖子评论数
            $dumpdata['comment_count'] = $subject['comment_count'];
            //帖子赞数
            $dumpdata['praise_count'] = $subject['fancied_count'];
            //帖子阅读数
            $dumpdata['view_count'] = $subject['view_count'];
            //帖子发布时间
            $dumpdata['created'] = $subject['created'];
            //帖子标签
            if (!empty($subject['group_labels'])) {
                $dumpdata['labels'] = array();
                foreach ($subject['group_labels'] as $label) {
                    $dumpdata['labels'][] = $label['title'];
                }
                $dumpdata['labels'] = implode(',', $dumpdata['labels']);
            } else {
                $dumpdata['labels'] = 'NULL';
            }
            //帖子关联sku
            if (!empty($subject['items'])) {
                $dumpdata['items'] = array();
                foreach ($subject['items'] as $item) {
                    $dumpdata['items'][] = $item['item_id'];
                }
                $dumpdata['items'] = implode(',', $dumpdata['items']);
            } else {
                $dumpdata['items'] = 'NULL';
            }
            //帖子标题
            $dumpdata['title'] = !empty(trim($subject['title'])) ? $subject['title'] : 'NULL';
            //帖子文本
            $dumpdata['text'] = !empty(trim($subject['text'])) ? $subject['text'] : 'NULL';
            //写入文本
            $put_content = implode("\t", $dumpdata);
            file_put_contents($this->dumpSubjectFile, $put_content . "\n", FILE_APPEND);
        }
        
        if (!empty($userInfos)) {
            foreach ($userInfos as $user) {
                $dumpuser = array();
                //用户ID
                $dumpuser['id'] = $user['user_id'];
                //用户类型
                if ($user['is_supplier'] == 1) {
                    $dumpuser['user_type'] = 'supplier';
                } else if ($user['is_experts']) {
                    $dumpuser['user_type'] = 'talent';
                } else {
                    $dumpuser['user_type'] = 'normal';
                }
                //粉丝数
                $dumpuser['fans_count'] = $user['fans_count'];
                //帖子发布数
                $dumpuser['pic_count'] = $user['pic_count'];
                //专栏发布数
                $dumpuser['article_count'] = $user['article_count'];
                //用户描述
                $dumpuser['desc'] = !empty($user['experts_info']['desc']) ? $user['experts_info']['desc'] : 'NULL';
                //写入文本
                $put_content = implode("\t", $dumpuser);
                file_put_contents($this->dumpUserFile, $put_content . "\n", FILE_APPEND);
            }
        }
        
        //写入本次处理的最大event_id
        if (isset($maxId)) {
            fseek($fpLastIdFile, 0, SEEK_SET);
            ftruncate($fpLastIdFile, 0);
            fwrite($fpLastIdFile, $maxId);
        }
        flock($fpLastIdFile, LOCK_UN);
        fclose($fpLastIdFile);
    }
}