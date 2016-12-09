<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\Order\Order as OrderData;
class Order {
    
    public $orderData;
    
    public function __construct() {
        $this->orderData = new OrderData();
    }
    
    /**
     * 根据订单编号获取订单信息
     */
    public function getOrderInfoByOrderCode($orderCodes){
        $orderData = $this->orderData->getOrderInfoByOrderCode($orderCodes);
        return $orderData;
    }
    
    /**
     * 根据订单ID获取订单信息
     */
    public function getOrderInfoByIds($orderIds){
        $orderData = $this->orderData->getOrderInfoByIds($orderIds);
        return $orderData;
    }
}