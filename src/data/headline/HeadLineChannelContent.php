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
        $where[] = ['channel_id', $channelId];
        $where[] = ['page', $page];
        $data = $this->getRows($where);
        $result = [];
        foreach ($data as $v) {
            $result[$v['channel_id']] = $v;
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