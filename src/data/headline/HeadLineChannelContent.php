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
            $v['ext_info'] = json_decode($v['ext_info'], true);
            $result[$v['relation_id'].'_'.$v['relation_type']] = $v;
        }
        return $result;
    }

    /**
     * 添加头条
     */
    public function addOperateHeadLine($headlineData)
    {
        $data = $this->insert($headlineData);
        return $data;
    }

    /**
     * 更新头条
     */
    public function updateHeadlineById($id, $setData)
    {
        $where[] = ['id', $id];
        $data = $this->update($setData, $where);
        return $data;
    }

    /**
     * 删除头条
     */
    public function delHeadlineById($id)
    {
        $where[] = ['id',$id];
        $data = $this->delete($where);
        return $data;
    }

    /**
     * 根据ID查询头条
     */
    public function getHeadLineById($id) {
        if (intval($id) <= 0) {
            return false;
        }
        $where[] = [':eq','id', $id];
        $data = $this->getRow($where);
        if (!empty($data)) {
            $data['ext_info'] = json_decode($data['ext_info'], true);
        }
        return $data;
    }
}