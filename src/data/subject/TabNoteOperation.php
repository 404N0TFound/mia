<?php
namespace mia\miagroup\Data\Subject;

class TabNoteOperation extends \DB_Query
{
    protected $dbResource = 'miagroup';
    protected $tableName = 'group_tab_note_operation';
    protected $mapping = [];

    /**
     * 获取运营信息
     * @param $conditions
     * @return array
     */
    public function getBatchOperationInfos($conditions)
    {
        if (isset($conditions['tab_id'])) {
            $where[] = ['tab_id', $conditions['tab_id']];
        }
        if (isset($conditions['page'])) {
            $where[] = ['page', $conditions['page']];
        }
        $date = date("Y-m-d H:i:s");
        $where[] = [':lt', 'start_time', $date];
        $where[] = [':gt', 'end_time', $date];
        $data = $this->getRows($where);
        foreach ($data as $v) {
            //http转https
            $result[$v['relation_id'] . '_' . $v['relation_type']] = array_merge($v, ['ext_info' => json_decode(str_replace('http:\/\/', 'https:\/\/', strval($v['ext_info'])), true)]);
        }
        return $result;
    }
}