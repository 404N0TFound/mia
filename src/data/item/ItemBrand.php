<?php
namespace mia\miagroup\Data\Item;

use Ice;

class ItemBrand extends \DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'item_brand';

    protected $mapping = array();
    
    /**
     * 根据item_ids获取品牌信息
     */
    public function getBatchBrandInfoByIds($ids)
    {
        if (empty($ids)) {
            return array();
        }
        $where[] = ['id',$ids];
        $data = $this->getRows($where,'id,name,chinese_name,english_name,address,notes,score');
        $result = array();
        if (!empty($data)) {
            foreach ($data as $value) {
                $result[$value['id']] = $value;
            }
        }
        return $result;
    }
}