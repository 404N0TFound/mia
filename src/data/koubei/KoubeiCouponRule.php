<?php
namespace mia\miagroup\Data\Koubei;

use Ice;

class KoubeiCouponRule extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_coupon_rule';

    protected $mapping = array();

    /*
     * 代金券发放设置
     * */
    function koubeiCouponRule($param, $order_by, $offset, $limit, $condition)
    {
        $where = [];
        $field = '*';
        $where[] = ['status', 1];
        if(!empty($param)) {
            $where [] = [
                ':and', [
                    ['brand_id', $param['brand_id']],
                    [':or', ['item_id', $param['item_id']]],
                    [':or', ['category_id', $param['category_id']]]
                ]
            ];
        }
        if(!empty($condition)) {
            if(!empty($condition['where'])) {
                if(!empty($condition['where']['brand_id'])) {
                    $where[] = ['brand_id', $condition['where']['brand_id']];
                }
                if(!empty($condition['where']['category_id'])) {
                    $where[] = ['category_id', $condition['where']['category_id']];
                }
                if(!empty($condition['where']['item_id'])) {
                    $where[] = ['item_id', $condition['where']['item_id']];
                }
            }
            if(!empty($condition['field'])){
                $field = $condition['field'];
            }
        }
        $data = $this->getRows($where, $field, $limit, $offset, $order_by);
        return $data;
    }
}