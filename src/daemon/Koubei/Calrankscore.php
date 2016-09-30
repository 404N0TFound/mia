<?php
namespace mia\miagroup\Daemon\Koubei;

use mia\miagroup\Model\Koubei as KoubeiModel;
use mia\miagroup\Data\Koubei\Koubei as KoubeiData;

/**
 * 口碑排序分数每日更新
 */
class Calrankscore extends \FD_Daemon {

    private $koubeiModel;
    private $koubeiData;
    private $blackWord;

    public function __construct() {
        $this->koubeiModel = new KoubeiModel();
        $this->koubeiData = new KoubeiData();
    }

    public function execute() {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        for ($i = 1; $i <= 12; $i ++) {
            $startTime = date('Y-m-d', time() - (86400 * ($i + 1) * 30));
            $endTime = date('Y-m-d', time() - (86400 * $i * 30));
            $datas = $this->koubeiData->getKoubeiListByTime($startTime, $endTime, 0, false);
            foreach ($datas as $data) {
                $setData = null;
                //加精修正
                if ($data['rank'] == 1) {
                    $data['immutable_score'] += 6;
                    $data['rank_score'] += 6;
                    $setData[] = array('immutable_score', $data['immutable_score']);
                }
                //时间分数修正
                $data['rank_score'] -= $i * 0.5;
                $setData[] = array('rank_score', $data['rank_score'] > 0 ? $data['rank_score'] : 0);
                $this->koubeiData->updateKoubeiInfoById($data['id'], $setData);
            }
        }
    }
}