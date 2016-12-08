<?php
namespace mia\miagroup\Data\Order;

use Ice;

class Order extends \DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'orders';

    protected $mapping = array();
    
    //根据订单编号获取订单信息
    public function getOrderInfoByOrderCode($orderParams){
        $where = array();
        if(isset($orderParams['order_code']) && !empty($orderParams['order_code'])){
            $where[] = ['order_code', $orderParams['order_code']];
        }
        if(isset($orderParams['order_id']) && !empty($orderParams['order_id'])){
            $where[] = ['id', $orderParams['order_id']];
        }
        if (empty($where)) {
            return array();
        }
        
        $data = $this->getRows($where);

        if(empty($data)){
            return array();
        }else{
            return $data;
        }
    }
    

}