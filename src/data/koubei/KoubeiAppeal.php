<?php
namespace mia\miagroup\Data\Koubei;

use Ice;

class KoubeiAppeal extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'koubei_appeal';

    protected $mapping = array();
    
    /**
     * 保存口碑申诉
     */
    public function addKoubeiAppeal($appeal_info)
    {
        $new_id = $this->insert($appeal_info);
        return $new_id;
    }

    /**
     * 更新申诉信息
     */
    public function updateAppealInfoByKoubeiId($appeal_id, $appeal_info)
    {
        if (empty($appeal_id) || empty($appeal_info) || !is_array($appeal_info)) {
            return false;
        }
        $set_data = array();
        foreach ($appeal_info as $k => $v) {
            $set_data[] = array($k, $v);
        }
        $where[] = array('id', $appeal_id);
        $data = $this->update($set_data, $where);
        return $data;
    }
    
    /**
     * 根据ID查询申诉信息
     */
    public function getAppealInfoByIds($appeal_ids, $status = array()) 
    {
        if (empty($appeal_ids)) {
            return array();
        }
        $result = array();
        $where[] = ['id', $appeal_ids];
        if (!empty($status)) {
            $where[] = ['status', $status];
        }
        $data = $this->getRows($where);
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[$v['id']] = $v;
            }
        }
        return $result;
    }
}