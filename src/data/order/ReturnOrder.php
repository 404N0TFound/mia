<?php
namespace mia\miagroup\Data\Order;

use Ice;

class ReturnOrder extends \DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'return_items';

    protected $mapping = array();

    /*
     * 获取退货信息
     * */
    public function returnInfo($order_code, $item_id) {
        if (empty($order_code) || empty($item_id)) {
            return array();
        }
        $where[] = ['order_code', $order_code];
        $where[] = ['item_id', $item_id];
        $where[] = [':ne','status', 0];
        $result = $this->getRows($where);
        return $result;
    }

    public function getReturnInfo($returnNums)
    {
        if (empty($returnNums)) {
            return array();
        }
        $where[] = ['returns.id',$returnNums];
        $join = 'LEFT JOIN returns ON return_items.return_id = returns.id';
        $data = $this->getRows($where, 'return_items.item_id,returns.id', FALSE, 0, FALSE, $join);

        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[$v['id']][] = $v["item_id"];
            }
        }
        return $result;
    }


}