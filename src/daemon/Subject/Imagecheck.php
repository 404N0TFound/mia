<?php
namespace mia\miagroup\Daemon\Subject;

/**
 * 帖子图片检查
 */
class Imagecheck extends \FD_Daemon
{
    private $mode;
    private $lastIdFile;
    private $tempFilePath;

    public function __construct()
    {
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $this->tempFilePath = $runFilePath . '/subject/';
        $this->lastIdFile = $this->tempFilePath . 'subject_image_check_last_id';
    }

    public function execute()
    {
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
        // 初始化
        if($lastId <= 0) {
            $initId = $subjectData->getRow(array(), 'max(id) as maxid');
            $initId = intval($initId['maxid']);
            fseek($fpLastIdFile, 0, SEEK_SET);
            ftruncate($fpLastIdFile, 0);
            fwrite($fpLastIdFile, $initId);
            flock($fpLastIdFile, LOCK_UN);
            fclose($fpLastIdFile);
            return ;
        }

        $orderBy = FALSE;
        $where = [];
        $where[] = ['status', 1];
        //暂时先只处理口碑图片
        $where[] = ['source', 2];
        $where[] = [':gt','id', $lastId];
        $where[] = [':ne','ext_info', ''];
        $where[] = [':ne','image_url', ''];
        $limit = 50;
        $field = 'id, ext_info';
        $data = $subjectData->getRows($where, $field, $limit, 0, $orderBy);
        if (empty($data)) {
            return ;
        }
        $image_util = new \mia\miagroup\Util\ImageUtil();
        foreach ($data as $v) {
            if (isset($maxId)) { //获取最大event_id
                $maxId = $v['id'] > $maxId ? $v['id'] : $maxId;
            } else {
                $maxId = $v['id'];
            }
            $ext_info = json_decode($v['ext_info'], true);
            if (empty($ext_info['image'])) {
                continue;
            }
            $has_qrcode = false;
            $host = \F_Ice::$ins->workApp->config->get('app.url.img_url');
            foreach ($ext_info['image'] as $k => $v_image) {
                //检查是否包含二维码
                $res = $image_util->qrdecode($host . $v_image['url']);
                if ($res && $res['status'] == 0 && !empty($res['symbols'])) {
                    foreach ($res['symbols'] as $symbols) {
                        if ($symbols['type'] == 'QRCODE') {
                            $ext_info['image'][$k]['is_hidden'] = 1;
                            $has_qrcode = true;
                            //同时设置美化图片不可见
                            if (!empty($ext_info['beauty_image'])) {
                                $ext_info['beauty_image'][$k]['is_hidden'] = 1;
                            }
                            //同时检查封面是否是二维码图
                            if (!empty($ext_info['cover_image']) && $ext_info['cover_image']['url'] == $v_image['url']) {
                                unset($ext_info['cover_image']);
                            }
                        }
                    }
                }
            }
            if ($has_qrcode == true) {
                //隐藏二维码图片
                $ext_info = json_encode($ext_info);
                $set_data[] = ['ext_info', $ext_info];
                $subjectData->updateSubject($set_data, $v['id']);
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