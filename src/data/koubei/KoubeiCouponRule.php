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
    function koubeiCouponRule($param, $order_by, $offset, $limit)
    {
        $where[] = ['status', 1];
        $where[] = ['brand_id', $param['brand_id']];
        $where[] = ['category_id', $param['category_id']];
        $where[] = ['sku_id', $param['item_id']];
        $fields = 'ext_info,start_time,end_time';
        $data = $this->getRow($where, $fields, $limit, $offset, $order_by);
        return $data;
    }
}
