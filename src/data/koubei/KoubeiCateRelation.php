<?php
namespace mia\miagroup\Data\Koubei;

use Ice;

class KoubeiCateRelation extends \DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'app_menu_category_v2';

    protected $mapping = array();



    /*
     * 查询四级类目列表
     * */
    public function fourList($three_cate, $flag)
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
        $cateList = array_column($result,$flag);
        $cateString = implode(",",$cateList);
        return $cateString;
    }

    /*
     * 获取ID 类型
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

}