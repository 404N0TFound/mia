<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\Order\Order as OrderData;
class Order {
    
    public $orderData;
    
    public function __construct() {
        $this->orderData = new OrderData();
    }
    
    //根据订单编号获取订单信息
    public function getOrderInfoByOrderCode($orderParams){
        $orderData = $this->orderData->getOrderInfoByOrderCode($orderParams);
        return $orderData;
    }

}