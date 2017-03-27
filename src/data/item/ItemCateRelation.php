<?php
namespace mia\miagroup\Data\Item;

use Ice;

class ItemCateRelation extends \DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'app_menu_category_v2';
    protected $tableNewCategory = 'item_category_ng';
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
     * 根据四级分类查询父类目路径
     * */
    public function getparentCategoryPath($category_id_ng) {

        if(empty($category_id_ng)) {
            return '';
        }
        $this->tableName = $this->tableNewCategory;
        $where[] = ['status', 1];
        $where[] = ['id',$category_id_ng];
        $fields = "path";
        $result = $this->getRows($where,$fields);
        return $result[0]['path'];
    }
}