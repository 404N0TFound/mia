<?php
namespace mia\miagroup\Daemon\Koubei;

use mia\miagroup\Data\Koubei\Koubei as KoubeiData;
use mia\miagroup\Data\Koubei\KoubeiScore as KoubeiScoreData;

/**
 * 口碑排序分数准实时修正
 */
class Correctscore extends \FD_Daemon {

    private $koubeiData;
    private $koubeiScoreData;
    private $lastIdFile;

    public function __construct() {
        $this->koubeiData = new KoubeiData();
        $this->koubeiScoreData = new KoubeiScoreData();
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $tempFilePath = $runFilePath . '/koubei/';
        $this->lastIdFile = $tempFilePath . 'correct_score_last_id';
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
            $maxId = $this->koubeiScoreData->getMaxId();
            //写入最大ID
            fseek($fpLastIdFile, 0, SEEK_SET);
            ftruncate($fpLastIdFile, 0);
            fwrite($fpLastIdFile, $maxId);
            flock($fpLastIdFile, LOCK_UN);
            fclose($fpLastIdFile);
            return;
        }
        
        //拉取待修正的口碑id
        $data = $this->koubeiScoreData->getListById($lastId, 1000);
        if (empty($data)) {
            return ;
        }
        $koubeiIds = array();
        foreach ($data as $value) {
            $koubeiIds[] = $value['id'];
        }
        $koubeiInfos = $this->koubeiData->getBatchKoubeiByIds($koubeiIds, array());
        foreach ($data as $value) {
            if (isset($maxId)) { //获取最大event_id
                $maxId = $value['id'] > $maxId ? $value['id'] : $maxId;
            } else {
                $maxId = $value['id'];
            }
            if (!isset($koubeiInfos[$value['id']])) {
                continue;
            }
            $tmpKoubei = $koubeiInfos[$value['id']];
            $score = null;
            $reduce = 0;
            switch ($value['flag']) {
                case 1: //差评往一星分数修正
                    $reduce = ($tmpKoubei['score'] - 1) * 2;
                    break;
                case 2: //中评往二星分数修正
                    $reduce = ($tmpKoubei['score']-2)>0 ? (($tmpKoubei['score'] - 2) * 2) : 0;
                    break;
                case 3: //好评往最高3星修正
                    $reduce = ($tmpKoubei['score']-3)<0 ? ($tmpKoubei['score'] - 3) * 2 : 0;
                    break;
            }
            $score[] = array('machine_score', $value['flag']); //机器评分
            if ($reduce != 0) {
                $rankScore = (($tmpKoubei['rank_score'] - $reduce) > 0) ? $tmpKoubei['rank_score'] - $reduce : 0;
                $immutableScore = (($tmpKoubei['immutable_score'] - $reduce) > 0) ? $tmpKoubei['immutable_score'] - $reduce : 0;
                $score[] = array('rank_score', $rankScore);
                $score[] = array('immutable_score', $immutableScore);
            }
            $this->koubeiData->updateKoubeiInfoById($value['id'], $score);
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
