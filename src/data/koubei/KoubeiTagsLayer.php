<?php
namespace mia\miagroup\Data\Koubei;

use Ice;

class KoubeiTagsLayer extends \DB_Query
{

    protected $dbResource = 'miagroup';

    protected $tableName = 'koubei_tags_layer';

    protected $mapping = array();

    /**
     * 添加记录
     */
    public function addTagsLayer($setData)
    {
        $result = $this->insert($setData);
        return $result;
    }


    /**
     * 单条查询
     */
    public function getInfo($where)
    {
        $result = $this->getRow($where, 'id,root,parent,tag_id');
        return $result;
    }

    /**
     * 更新
     */
    public function updateLayer($setData, $where)
    {
        if (empty($setData) || empty($where)) {
            return false;
        }
        $data = $this->update($setData, $where);
        return $data;
    }

    /**
     * 查询
     */
    public function getList($where)
    {
        $result = $this->getRows($where, 'id,root,parent,tag_id');
        return $result;
    }

    /**
     * 树结构显示
     */
    public function showTrees()
    {
        $rootList = $this->query("SELECT t1.id,t1.root,t1.parent,t1.tag_id FROM koubei_tags_layer t1 LEFT JOIN koubei_tags_layer  t2 on t1.id=t2.id WHERE t1.root=t2.parent AND t1.parent=t2.tag_id AND t1.tag_id=t2.root");
        $rootList = $this->getNextChild($rootList);
        return $rootList;
    }

    /**
     * 递归遍历树
     * @param $parentList
     * @param int $check
     * @return mixed
     */
    public function getNextChild(&$parentList)
    {
        foreach ($parentList as &$v) {
            $childList = $this->getRows(["parent", $v["tag_id"]], 'id,root,parent,tag_id');
            foreach ($childList as $key => $val) {
                if ($val['root'] == $val['parent'] && $val['parent'] == $val['tag_id'] && $val['tag_id'] == $val['root']) {
                    unset($childList[$key]);
                }
            }
            if(!empty($childList)){
                $v['child'] = $childList;
                $this->getNextChild($v['child']);
            }
        }
        return $parentList;
    }
}