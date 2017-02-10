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
    public function fourCategoryList($three_cate)
    {
        if (empty($three_cate)) {
            return '';
        }
        $where[] = ['pid', $three_cate];
        $where[] = ['is_show',1];
        $where[] = ['status',1];
        $fields = "cid";
        $result = $this->getRows($where,$fields);
        if(empty($result)){
            return '';
        }
        $cateList = array_column($result,'cid');
        $cateString = implode(",",$cateList);
        return $cateString;
    }

}