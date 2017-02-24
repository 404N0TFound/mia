<?php
namespace mia\miagroup\Data\Item;

use Ice;

class ItemCateRelation extends \DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'app_menu_category_v2';

    protected $mapping = array();



    /*
     * 查询四级类目列表
     * */
    public function cateFourList($three_cate, $flag)
    {
        if (empty($three_cate) || empty($flag)) {
            return '';
        }
        $where[] = ['pid', $three_cate];
        $where[] = ['is_show',1];
        $where[] = ['status',1];
        $fields = $flag;
        $result = $this->getRows($where,$fields);
        if(empty($result)){
            return '';
        }
        $list = array_column($result,$flag);
        return array_unique($list);
    }

    /*
     * 获取ID 类型（预留）
     * */
    public function idType($id){
        if (empty($id)) {
            return '';
        }
        $where[] = ['id', $id];
        $where[] = ['is_show',1];
        $where[] = ['status',1];
        $fields = "type";
        $result = $this->getRows($where,$fields);
        if(empty($result)){
            return '';
        }
        return $result[0]['type'];
    }

    /*
     * 获取品牌名称列表
     * */
    public function brandNameList($ids){
        if (empty($ids) && !is_array($ids)) {
            return array();
        }
        $itemsIds = implode(',', $ids);
        $sql = "select id , if(chinese_name != '',chinese_name,name) as name from item_brand where id in ({$itemsIds})";
        $res = $this->query($sql);
        if(!empty($res)){
            return $res;
        }
        return array();
    }

}