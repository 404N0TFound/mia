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

    public function execute() {}

    public function cal_rank_score() {
        for ($i = 1; $i <= 12; $i ++) {
        }
        while (1) {
            $lists = $this->getKoubeiInfo();
            foreach ($lists as $koubei) {
                $res = $this->calScore($koubei);
                $update = $this->updateKoubei($koubei['id'], $res);
            }
            sleep(1);
        }
    }

    private function getKoubeiInfo() {
        // 搜索update_time是0的或者一个月以前的口碑列表
        $sql = "select kb.id, kb.content,kb.score, kb.created_time,kb.rank, kb.grade,kb.rank_score,kb.rank,kb.user_id,kb.immutable_score, pic.local_url, kb.update_time from {$this->table_koubei} as kb  left join {$this->table_koubei_pic} as pic on kb.id = pic.koubei_id where kb.status = 2 and ( kb.update_time ='0000-00-00 00:00:00' OR update_time < date_sub(curdate(), interval 1 month) ) order by kb.created_time desc limit 1000";
        $lists = $this->koubeiData->query($sql);
        
        return $lists;
    }

    private function calScore($data) {
        $return = array();
        if (empty(floatval($data['immutable_score']))) {
            $return['immutable_score'] = floatval($this->calImmutableScore($data));
            $return['rank_score'] = floatval($this->calTimeScore($data['created_time'])) + floatval($return['immutable_score']);
        }
        return $return;
    }

    private function calImmutableScore($data) {
        $hasPic = 0;
        if (!empty($data['local_url'])) {
            $hasPic = 1;
        }
        $content_count = mb_strlen($data['content'], 'utf-8');
        $immutable_score = 1 * 0.2;
        
        if ($content_count > 100) {
            $immutable_score = 10 * 0.2;
        } else if ($content_count > 50) {
            $immutable_score = 8 * 0.2;
        } else if ($content_count > 30) {
            $immutable_score = 5 * 0.2;
        } else if ($content_count > 10) {
            $immutable_score = 3 * 0.2;
        }
        
        $immutable_score += $data['score'] * 0.5 + 0.3 * 10 * $hasPic + $data['rank'] * 6;
        return $immutable_score;
    }

    private function calTimeScore($created) {
        $date1 = date_create(date("Y-m-d"));
        $date2 = date_create(date("Y-m-d", strtotime($created)));
        $diff = date_diff($date1, $date2);
        $key = 0;
        if (!empty($diff->y)) {
            $key = 13;
        } else if ($diff->m) {
            $key = $diff->m + 1;
        } else if (empty($diff->m)) {
            $key = 0;
        }
        $map = array(
            '0' => 12,'1'=> 11,'2'=> 11,'3'=> 10,'4'=> 9,'5'=> 8,'6'=> 7,
            '7' => 6,'8'=> 5,'9'=> 4,'10'=> 3,'11'=> 2,'12'=> 1,'13'=> 0,
        );
        return $map[$key] * 0.1;
    }
    
    private function prePareBlackWord() {
        $blackListFile = \F_Ice::$ins->workApp->config->get('app.run_path') . '';
    }

    private function updateKoubei($id, $set) {
        $where = array('id' => $id);
        $set['update_time'] = date("Y-m-d H:i:s");
        $res = $this->db_write->set($set)
            ->where($where)
            ->update($this->table_koubei);
        return $res;
    }
    
}