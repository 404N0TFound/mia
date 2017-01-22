<?php
namespace mia\miagroup\Daemon\Koubei;

use mia\miagroup\Data\Koubei\Koubei as KoubeiData;

/**
 * 口碑发布补发代金券
 */
class Issuereward extends \FD_Daemon {

    private $koubeiData;
    private $lastIdFile;

    public function __construct() {
        $this->koubeiData = new KoubeiData();
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $tempFilePath = $runFilePath . '/koubei/';
        $this->lastIdFile = $tempFilePath . 'issue_coupons_last_id';
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
        
        //如果lastId为空，说明程序从未运行过，获取待更新数据的最大ID作为初始化数据
        if ($lastId <= 0) {
            $maxId = $this->koubeiData->getRow(array(), 'MAX(`id`) as id')['id'];
            //写入最大ID
            fseek($fpLastIdFile, 0, SEEK_SET);
            ftruncate($fpLastIdFile, 0);
            fwrite($fpLastIdFile, $maxId);
            flock($fpLastIdFile, LOCK_UN);
            fclose($fpLastIdFile);
            return;
        }
        
        //拉取新发布的5星口碑
        $where = [];
        $where[] = [':ge','id', $lastId];
        $where[] = ['auto_evaluate', 0];
        $where[] = ['score', 5];
        $where[] = ['status', 2];
        $data = $this->koubeiData->getRows($where, 'id, item_id, subject_id, score', 1000);
        if (empty($data)) {
            return ;
        }
        $subjectIds = array();
        foreach ($data as $value) {
            if (!empty($value)) {
                $subjectIds[] = $value['subject_id'];
            }
        }
        $subjectService = new \mia\miagroup\Service\Subject();
        $subjectInfos = $subjectService->getBatchSubjectInfos($subjectIds, 0, array())['data'];
        foreach ($data as $value) {
            if (isset($maxId)) { //获取最大event_id
                $maxId = $value['id'] > $maxId ? $value['id'] : $maxId;
            } else {
                $maxId = $value['id'];
            }
            if (!isset($subjectInfos[$value['subject_id']])) {
                continue;
            }
            //是否是首评
            $where = [];
            $where[] = [':lt','id', $value['id']];
            $where[] = ['item_id', $value['item_id']];
            $exist = $this->koubeiData->getRow($where, 'id');
            if ($exist) {
                continue;
            }
            $subject = $subjectInfos[$value['subject_id']];
            if ((mb_strlen($subject['text']) > 20) && !empty($subject['image_infos'])) {
                $couponRemote = new \mia\miagroup\Remote\Coupon();
                $batch_code = \F_Ice::$ins->workApp->config->get('batchdiff.koubeibatch')['batch_code']['test'];
                if (!empty($batch_code)) {
                    $bindCouponRes = $couponRemote->bindCouponByBatchCode($subject['user_id'], $batch_code);
                    if (!$bindCouponRes) {
                        $bindCouponRes = $couponRemote->bindCouponByBatchCode($subject['user_id'], $batch_code);
                    }
                }
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
