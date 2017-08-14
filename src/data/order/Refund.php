<?php
namespace mia\miagroup\Data\Order;

use Ice;

class Refund extends \DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'new_refund_request';

    protected $mapping = array();

    public function getRefundInfo($RefundNums)
    {
        if (empty($RefundNums)) {
            return array();
        }
        $where[] = ['refund_request_id',$RefundNums];
        $data = $this->getRows($where, 'refund_request_id,amount');

        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[$v['refund_request_id']] = $v["amount"];
            }
        }
        return $result;
    }
}