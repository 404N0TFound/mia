<?php
namespace mia\miagroup\Data\Order;

use Ice;

class Order extends \DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'orders';

    protected $mapping = array();
    
    //根据订单编号获取订单信息
    public function getOrderInfoByOrderCode($orderCode){
        if(empty($orderCode)){
            return array();
        }
        $where = array();
        $where[] = ['order_code', $orderCode];
        
        $data = $this->getRow($where);

        if(empty($data)){
            return array();
        }else{
            return $data;
        }
    }
    

}