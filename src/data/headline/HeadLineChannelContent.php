<?php
namespace mia\miagroup\Data\HeadLine;

class HeadLineChannelContent extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_headline_channel_content';

    protected $mapping = array();

    /**
     * 根据头条栏目获取头条
     */
    public function getHeadLinesByChannel($channelId, $page=1, $timeStatus=1)
    {
        if (empty($channelId)) {
            return [];
        }
        $where[] = [':eq','channel_id', $channelId];
        if (intval($page) > 0) {
            $where[] = [':eq','page', $page];
        }
        //获取在有效期内的头条
        if(intval($timeStatus) > 0){
            $where[] = [':le', 'begin_time', date('Y-m-d H:i:s',time())];
            $where[] = [':ge', 'end_time', date('Y-m-d H:i:s',time())];
        }

        $orderBy = "begin_time desc ";
        
        $data = $this->getRows($where,'*',false,0,$orderBy);
        $result = [];
        foreach ($data as $v) {
            //http转https
            $v['ext_info'] = str_replace('http:\/\/', 'https:\/\/', strval($v['ext_info']));
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
            //http转https
            $data['ext_info'] = str_replace('http:\/\/', 'https:\/\/', strval($data['ext_info']));
            $data['ext_info'] = json_decode($data['ext_info'], true);
        }
        return $data;
    }
    
    /**
     * 根据relation_id/type查询头条
     */
    public function getHeadLineByRelationId($relation_id, $relation_type) {
        if (intval($relation_id) <= 0) {
            return false;
        }
        $where[] = [':eq','relation_id', $relation_id];
        $where[] = [':eq','relation_type', $relation_type];
        $data = $this->getRow($where);
        if (!empty($data)) {
            //http转https
            $data['ext_info'] = str_replace('http:\/\/', 'https:\/\/', strval($data['ext_info']));
            $data['ext_info'] = json_decode($data['ext_info'], true);
        }
        return $data;
    }
}