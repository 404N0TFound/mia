<?php 
namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Data\Subject\Subject;
use mia\miagroup\Data\Koubei\Koubei as KoubeiData;

class Koubei extends \FD_Daemon {
    
    public function execute() {
        $this->repairSubjectRelation();
    }
    
    /**
     * 修复没有关联蜜芽圈帖子的口碑数据
     */
    public function repairSubjectRelation() {
        $subjectData = new Subject();
        $koubeiData = new KoubeiData();
        $startDate = '2016-08-30';
        $endDate = '2016-09-01';
        
        $where = array();
        $where[] = array(':gt', 'created', $startDate);
        $where[] = array(':lt', 'create_time', $endDate);
        $subjects = $subjectData->getRows($where, 'id, ext_info');
        
        $i = 0;
        foreach ($subjects as $subject) {
            $i ++;
            if ($i % 100 == 0) {
                sleep(1);
            }
            $koubeiId = json_decode($subject['ext_info'], true);
            $koubeiId = $koubeiId['koubei']['id'];
            if (intval($koubeiId) > 0) {
                $where = array();
                $where[] = array(':eq', 'id', $koubeiId);
                $koubeiInfo = $koubeiData->getRow($where, 'id, subject_id');
                if (intval($koubeiInfo['subject_id']) == 0) {
                    $koubeiData->updateKoubeiBySubjectid($koubeiId, $subject['id']);
                }
            }
        }
    }
}