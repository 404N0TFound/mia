<?php
namespace mia\miagroup\Data\HeadLine;

class HeadLineChannelContent extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_headline_channel_content';

    protected $mapping = array();

    /**
     * 根据头条栏目获取头条
     */
    public function getHeadLinesByChannel($channelId, $page=1)
    {
        if (empty($channelId)) {
            return [];
        }
        $where[] = [':eq','channel_id', $channelId];
        if (intval($page) > 0) {
            $where[] = [':eq','page', $page];
        }
        $where[] = [':le', 'begin_time', date('Y-m-d H:i:s',time())];
        $where[] = [':ge', 'end_time', date('Y-m-d H:i:s',time())];
        $data = $this->getRows($where);
        $result = [];
        foreach ($data as $v) {
            $result[$v['relation_id'].'_'.$v['relation_type']] = $v;
        }
        return $result;
    }

    public function addOperateHeadLine($headlineData)
    {
        $data = $this->insert($headlineData);
        return $data;
    }

    public function updateHeadlineById($id, $setData)
    {
        $where[] = ['id', $id];
        $data = $this->update($setData, $where);
        return $data;
    }


    public function delHeadlineById($id)
    {
        $where[] = ['id',$id];
        $data = $this->delete($where);
        return $data;
    }


}