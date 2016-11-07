<?php
namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Data\Koubei\Koubei as KoubeiData;
use mia\miagroup\Data\Koubei\KoubeiScore as KoubeiScoreData;

/**
 * 口碑排序分数准实时修正
 */
class Koubeimachinescore extends \FD_Daemon {

    private $koubeiData;
    private $koubeiScoreData;
    private $lastIdFile;

    public function __construct() {
        $this->koubeiData = new KoubeiData();
        $this->koubeiScoreData = new KoubeiScoreData();
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $tempFilePath = $runFilePath . '/koubei/';
        $this->lastIdFile = $tempFilePath . 'temp_koubei_machine_score_last_id';
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
        
        if ($lastId >= 1186237) {
            return;
        }
        
        //拉取待修正的口碑id
        $data = $this->koubeiScoreData->getListById($lastId, 3000);
        if (empty($data)) {
            return ;
        }
        foreach ($data as $value) {
            if (isset($maxId)) { //获取最大event_id
                $maxId = $value['id'] > $maxId ? $value['id'] : $maxId;
            } else {
                $maxId = $value['id'];
            }
            $koubeiIds1 = array();
            $koubeiIds2 = array();
            $koubeiIds3 = array();
            
            switch ($value['flag']) {
                case 1:
                    $koubeiIds1[] = $value['id'];
                    break;
                case 2:
                    $koubeiIds2[] = $value['id'];
                    break;
                case 3:
                    $koubeiIds3[] = $value['id'];
                    break;
            }
        }
        if (!empty($koubeiIds1)) {
            $score = array(array('machine_score', 1));
            $this->koubeiData->updateKoubeiInfoById($koubeiIds1, $score);
            sleep(1);
        }
        if (!empty($koubeiIds2)) {
            $score = array(array('machine_score', 2));
            $this->koubeiData->updateKoubeiInfoById($koubeiIds2, $score);
            sleep(1);
        }
        if (!empty($koubeiIds3)) {
            $score = array(array('machine_score', 3));
            $this->koubeiData->updateKoubeiInfoById($koubeiIds3, $score);
            sleep(1);
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
