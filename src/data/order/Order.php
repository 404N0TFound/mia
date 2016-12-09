<?php
namespace mia\miagroup\Data\Order;

use Ice;

class Order extends \DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'orders';

    protected $mapping = array();
    
    /**
     * 根据订单编号获取订单信息
     */
    public function getOrderInfoByOrderCode($orderCodes){
        if (empty($orderCodes)) {
            return array();
        }
        $where[] = ['order_code', $orderCodes];
        $data = $this->getRows($where);
        $result = array();
        if(!empty($data)){
            foreach($data as $v){
                $result[$v['order_code']] = $v;
            }
        }
        return $result;
    }
    
    /**
     * 根据订单ID获取订单信息
     */
    public function getOrderInfoByIds($orderIds){
        if (empty($orderIds)) {
            return array();
        }
        $where[] = ['id', $orderIds];
        $data = $this->getRows($where);
        $result = array();
        if(!empty($data)){
            foreach($data as $v){
                $result[$v['id']] = $v;
            }
        }
        return $result;
    }
}