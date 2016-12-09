<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Order as OrderModel;

class Order extends \mia\miagroup\Lib\Service {
    
    public $orderModel;
    
    public function __construct() {
        $this->orderModel = new OrderModel();
    }
    
    //根据订单编号获取订单信息（订单状态为已完成,且完成时间15天内的才可以发布口碑！）
    public function getOrderInfo($orderParams){
        $orderInfos = $this->orderModel->getOrderInfoByOrderCode($orderParams);
        return $this->succ($orderRes);
    }
    

    /**
     * 根据订单编号获取订单信息
     */
    public function getOrderInfoByOrderCode($orderCodes){
        $orderData = $this->orderModel->getOrderInfoByOrderCode($orderCodes);
        return $this->succ($orderData);
    }
    
    /**
     * 根据订单ID获取订单信息
     */
    public function getOrderInfoByIds($orderIds){
        $orderData = $this->orderModel->getOrderInfoByIds($orderIds);
        return $this->succ($orderData);
    }
}
