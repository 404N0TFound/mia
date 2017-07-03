<?php
namespace mia\miagroup\Daemon\Subject;

/**
 * 导出帖子数据
 * 全量导出：每天一次，每天0点重建数据文件夹，生成新的全量数据文件
 * 增量导出：每10分钟一次
 */
class Subjectdump extends \FD_Daemon {

    private $lastIdFile;
    private $dumpSubjectFile;
    private $dumpUserFile;
    private $mode;
    private $python_bin;
    private $negative_path;
    private $subjectData;

    public function __construct() {
        $this->python_bin = \F_Ice::$ins->workApp->config->get('app.daemon_python_bin');
        $this->negative_path = '/home/work/negative_notes/bin/';
        $this->subjectData = new \mia\miagroup\Data\Subject\Subject();
    }

    public function execute() {
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $tempFilePath = $runFilePath . '/subject/';
        $mode = $this->request->argv[0];
        if (empty($mode)) {
            return ;
        }
        $this->mode = $mode;
        switch ($this->mode) {
            case 'full_dump':
                $folderName = date('Ymd');
                $folderPath = $tempFilePath . $folderName . '/';
                $this->mk_dir($folderPath);
                $this->lastIdFile = $folderPath . 'subject_dump_last_id';
                $this->dumpSubjectFile = $folderPath . 'dump_subject_file_do_not_delete';
                $this->dumpUserFile = $folderPath . 'dump_user_file_do_not_delete';
                $this->dump_data();
                break;
            case 'incremental_dump':
                $folderName = date('YmdHi');
                $folderPath = $tempFilePath . $folderName . '/';
                $this->mk_dir($folderPath);
                $this->lastIdFile = $tempFilePath . 'subject_dump_incr_id';
                $this->dumpSubjectFile = $folderPath . 'dump_subject_file_do_not_delete';
                $this->dumpUserFile = $folderPath . 'dump_user_file_do_not_delete';
                $this->dump_data();
                file_put_contents($folderPath . 'done', date('Y-m-d H:i:s')); //增量导出数据后，记录时间戳
                //继续更新数据导出
                $this->dumpSubjectFile = $folderPath . 'dump_update_subject_do_not_delete';
                $this->dump_update_data();
                break;
        }
    }
    
    private function dump_data() {
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
            switch ($this->mode) {
                case 'full_dump': //全量导出数据，lastid初始为1
                    fseek($fpLastIdFile, 0, SEEK_SET);
                    ftruncate($fpLastIdFile, 0);
                    fwrite($fpLastIdFile, 1);
                    break;
                case 'incremental_dump': //增量导出数据，lastid初始为最大subjectid
                    $subjectData = new \mia\miagroup\Data\Subject\Subject();
                    $initId = $subjectData->getRow(array(), 'max(id) as maxid');
                    $initId = intval($initId['maxid']);
                    fseek($fpLastIdFile, 0, SEEK_SET);
                    ftruncate($fpLastIdFile, 0);
                    fwrite($fpLastIdFile, $initId);
                    break;
            }
            flock($fpLastIdFile, LOCK_UN);
            fclose($fpLastIdFile);
            return ;
        }
        
        //拉取新发布的帖子
        $where = [];
        $where[] = [':gt','id', $lastId];
        $source_config = \F_Ice::$ins->workApp->config->get('busconf.subject.source');
        $where[] = ['source', [$source_config['default'], $source_config['koubei'], $source_config['editor']]];
        $where[] = ['status', 1];
        $where[] = [':lt','created', date("Y-m-d H:i:s", strtotime("-3 minute"))];
        $data = $this->subjectData->getRows($where, 'id, user_id, ext_info, semantic_analys', 1000);
        if (empty($data)) {
            return ;
        }
        
        $maxId = $this->dump_subject($data, $lastId);
        
        //写入本次处理的最大event_id
        if (isset($maxId)) {
            fseek($fpLastIdFile, 0, SEEK_SET);
            ftruncate($fpLastIdFile, 0);
            fwrite($fpLastIdFile, $maxId);
        }
        flock($fpLastIdFile, LOCK_UN);
        fclose($fpLastIdFile);
    }
    
    private function dump_update_data() {
        //拉取有修改的帖子
        $key = \F_Ice::$ins->workApp->config->get('busconf.rediskey.subjectKey.subject_update_record.key');
        // 执行redis指令
        $redis = new \mia\miagroup\Lib\Redis();
        $subject_ids = array();
        for ($i = 0; $i < 1000; $i ++) {
            $subject_id = $redis->lpop($key);
            if ($subject_id) {
                $subject_ids[] = $subject_id;
            } else {
                break;
            }
        }
        if (empty($subject_ids)) {
            return ;
        }
        $where = [];
        $where[] = ['id', $subject_ids];
        $source_config = \F_Ice::$ins->workApp->config->get('busconf.subject.source');
        $where[] = ['source', [$source_config['default'], $source_config['koubei'], $source_config['editor']]];
        $where[] = ['status', 1];
        $data = $this->subjectData->getRows($where, 'id, user_id, ext_info, semantic_analys, created', 1000);
        if (empty($data)) {
            return ;
        }
        foreach ($data as $k => $v) {
            //发布不过3分钟的不处理，重新扔回队列
            if ((time() - strtotime($v['created'])) <= 180) {
                $redis->lpush($key, $v['id']);
                unset($data[$k]);
            }
        }
        
        $this->dump_subject($data);
    }
    
    private function dump_subject($data, $lastId = null) {
        //收集帖子ID、用户ID、口碑ID
        $subjectIds = array();
        $userIds = array();
        $koubeiIds = array();
        foreach ($data as $value) {
            $subjectIds[] = $value['id'];
            $userIds[] = $value['user_id'];
            $extInfo = json_decode($value['ext_info'], true);
            if (isset($extInfo['koubei']['id']) && intval($extInfo['koubei']['id']) > 0) {
                $koubeiIds[] = $extInfo['koubei']['id'];
            }
        }
        //获取帖子信息
        $subjectService = new \mia\miagroup\Service\Subject();
        $subjectInfos = $subjectService->getBatchSubjectInfos($subjectIds, 0, array('count', 'group_labels', 'album'))['data'];
        //获取帖子关联商品
        $pointTag = new \mia\miagroup\Service\PointTags();
        $subjectItemIds = $pointTag->getBatchSubjectItmeIds($subjectIds)['data'];
        //获取口碑信息
        $koubeiData = new \mia\miagroup\Data\Koubei\Koubei();
        $koubeiDatas = $koubeiData->getBatchKoubeiByIds($koubeiIds);
        $koubeiInfos = array();
        if (!empty($koubeiDatas)) {
            foreach ($koubeiDatas as $koubei) {
                $koubeiInfos[$koubei['subject_id']] = $koubei;
            }
        }
        //获取帖子作者
        if ($lastId !== null) {
            $where = [];
            $where[] = [':le','id', $lastId];
            $where[] = ['source', [1, 2]];
            $where[] = ['status', 1];
            $where[] = ['user_id', $userIds];
            $existUids = $this->subjectData->getRows($where, 'distinct(user_id)');
            $existUids = array_column($existUids, 'user_id');
            $userIds = array_diff($userIds, $existUids);
            $userService = new \mia\miagroup\Service\User();
            $userInfos = $userService->getUserInfoByUids($userIds, 0, array('count'))['data'];
        }
        
        $excludeUids = \F_Ice::$ins->workApp->config->get('busconf.subject.dump_exclude_uids');
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
            $extInfo = json_decode($value['ext_info'], true);
            //专栏或者视频过滤掉
            if (!empty($subject['album_article']) || !empty($subject['video_info'])) {
                continue;
            }
            //部分账号帖子屏蔽
            if (in_array($value['user_id'], $excludeUids)) {
                continue;
            }
            $dumpdata = array();
            //帖子ID
            $dumpdata['id'] = $subject['id'];
            //用户ID
            $dumpdata['user_id'] = $subject['user_id'];
            //帖子来源
            switch ($subject['source']) {
                case 1:
                    $dumpdata['subject_source'] = 'normal';
                    break;
                case 2:
                    $dumpdata['subject_source'] = 'koubei';
                    break;
                case 4:
                    $dumpdata['subject_source'] = 'editor';
                    break;
                default:
                    $dumpdata['subject_source'] = 'normal';
                    break;
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
                $labelIds = array();
                foreach ($subject['group_labels'] as $label) {
                    $labelIds[] = $label['id'];
                    $dumpdata['labels'][] = $label['title'];
                }
                $dumpdata['labels'] = implode(',', $dumpdata['labels']);
            } else {
                $dumpdata['labels'] = 'NULL';
            }
            //帖子关联sku
            if (!empty($subjectItemIds[$subject['id']])) {
                $dumpdata['items'] = implode(',', $subjectItemIds[$subject['id']]);
            } else {
                $dumpdata['items'] = 'NULL';
            }
            //帖子标题
            $dumpdata['title'] = !empty(trim($subject['title'])) ? $subject['title'] : 'NULL';
            //帖子文本
            $subject['text'] = str_replace("\t", ' ', $subject['text']);
            $subject['text'] = str_replace("\r\n", ' ', $subject['text']);
            $subject['text'] = str_replace("\n", ' ', $subject['text']);
            $dumpdata['text'] = !empty(trim($subject['text'])) ? $subject['text'] : 'NULL';
            //帖子是否被推荐
            $dumpdata['is_fine'] = intval($subject['is_fine']);
            //口碑用户评分
            $dumpdata['koubei_score'] = !empty($koubeiInfos[$value['id']]) ? $koubeiInfos[$value['id']]['score'] : 'NULL';
            //机器评分
            $dumpdata['machine_score'] = !empty($koubeiInfos[$value['id']]) ? $koubeiInfos[$value['id']]['machine_score'] : 'NULL';
            //关联标签ID
            $dumpdata['label_ids'] = !empty($labelIds) ? implode(',', $labelIds) : 'NULL';
            //好评差评识别
            if ($dumpdata['text'] == 'NULL' && $dumpdata['title'] == 'NULL') {
                $dumpdata['negative_result'] = 'NULL';
            } else {
                $dumpdata['negative_result'] = $value['semantic_analys'] ? $value['semantic_analys'] : 'NULL';
            }
            //封面图宽高
            if (!empty($subject['cover_image'])) {
                $dumpdata['cover_image_width'] = $extInfo['cover_image']['width'];
                $dumpdata['cover_image_height'] = $extInfo['cover_image']['height'];
            } else {
                $dumpdata['cover_image_width'] = 'NULL';
                $dumpdata['cover_image_height'] = 'NULL';
            }
            //帖子类型
            $dumpdata['subject_type'] = $subject['type'];
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
        return $maxId;
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