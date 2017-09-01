<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\Order\Order as OrderData;
use mia\miagroup\Data\Order\Refund;
use mia\miagroup\Data\Order\ReturnOrder as ReturnOrderData;
class Order {
    
    public $orderData;
    
    public function __construct() {
        $this->orderData = new OrderData();
        $this->returnData = new ReturnOrderData();
        $this->refundData = new Refund();
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
        $conditions["order_code"] = $orderCodes;
        $conditions["gather"] = "order_code";
        $orderData = $this->orderData->getOrderItemInfo($conditions);
        return $orderData;
    }

    public function getOrderSuperiorInfo($superiorCodes)
    {
        if(empty($superiorCodes)) {
            return [];
        }
        $conditions["superior_order_code"] = $superiorCodes;
        $conditions["gather"] = "superior_order_code";
        $orderData = $this->orderData->getOrderItemInfo($conditions);
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

    /**
     * 获取退货申请单信息
     * @param $returnNums
     * @return array
     */
    public function getReturnInfo($returnNums)
    {
        $returnData = $this->returnData->getReturnInfo($returnNums);
        return $returnData;
    }

    /**
     * 获取退款单信息
     * @param $RefundNums
     * @return array
     */
    public function getRefundInfo($RefundNums)
    {
        $refundData = $this->refundData->getRefundInfo($RefundNums);
        return $refundData;
    }
}