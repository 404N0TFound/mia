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
        $field = '*';

        $sql = "(SELECT {$field} FROM group_coupon_rule WHERE `status`=1 AND item_id= {$param['item_id']})";

        if (!empty($param['brand_id'])) {
            $sql .= " UNION (SELECT * FROM group_coupon_rule WHERE `status`=1 AND brand_id={$param['brand_id']})";
        }

        if(!empty($param['category_id'])) {
            $sql .= " UNION (SELECT * FROM group_coupon_rule WHERE `status`=1 AND category_id={$param['category_id']}";
        }

        $sql .= " order by {$order_by} limit {$limit}";
        $res = $this->query($sql);
        return $res;
    }
}
