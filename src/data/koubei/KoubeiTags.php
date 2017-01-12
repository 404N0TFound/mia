<?php
namespace mia\miagroup\Data\Koubei;

use Ice;

class KoubeiTags extends \DB_Query
{

    protected $dbResource = 'miagroup';

    protected $tableName = 'koubei_tags';

    protected $mapping = array();

    /**
     * 添加标签记录
     */
    public function addTag($data)
    {
        $result = $this->insert($data);
        return $result;
    }


    /**
     * 查询标签信息
     */
    public function getTagInfo($where)
    {
        $result = $this->getRow($where, 'id,tag_name,parent_id,positive');
        return $result;
    }

    /**
     * 更新标签
     */
    public function updateTags($setData, $where)
    {
        if (empty($setData) || empty($where)) {
            return false;
        }
        $data = $this->update($setData, $where);
        return $data;
    }

    /**
     * 查询标签信息
     */
    public function getTagsInfo($where)
    {
        $result = $this->getRows($where, 'id,tag_name,show_name,parent_id,positive');
        foreach ($result as $k=>&$v){
            if(!empty($v['show_name'])){
                $v['tag_name'] = $v['show_name'];
            }
            unset($v['show_name']);
            $res[] = $v;
        }
        return $res;
    }
}