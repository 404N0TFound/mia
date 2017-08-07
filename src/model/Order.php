<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\Order\Order as OrderData;
use mia\miagroup\Data\Order\ReturnOrder as ReturnOrderData;
class Order {
    
    public $orderData;
    
    public function __construct() {
        $this->orderData = new OrderData();
        $this->returnData = new ReturnOrderData();
    }
    
    /**
     * 根据订单编号获取订单信息
     */
    public function getOrderInfoByOrderCode($orderCodes){
        $orderData = $this->orderData->getOrderInfoByOrderCode($orderCodes);
        return $orderData;
    }

    public function getOrderItemInfo($orderCodes)
    {
        if(empty($orderCodes)) {
            return [];
        }
        $orderData = $this->orderData->getOrderItemInfo($orderCodes);
        return $orderData;
    }

    /**
     * 根据订单ID获取订单信息
     */
    public function getOrderInfoByIds($orderIds){
        $orderData = $this->orderData->getOrderInfoByIds($orderIds);
        return $orderData;
    }

    /*
     * 获取退货信息
     * */
    public function getReturnOrderInfo($order_code, $item_id) {
        $returnData = $this->returnData->returnInfo($order_code, $item_id);
        return $returnData;
    }
}