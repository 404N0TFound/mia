<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Order as OrderModel;

class Order extends \mia\miagroup\Lib\Service {
    
    public $orderModel;
    
    public function __construct() {
        parent::__construct();
        $this->orderModel = new OrderModel();
    }
    
    /**
     * 根据订单编号获取订单信息
     */
    public function getOrderInfoByOrderCode($orderCodes){
        $orderData = $this->orderModel->getOrderInfoByOrderCode($orderCodes);
        return $this->succ($orderData);
    }

    /**
     * 获取订单商品信息
     */
    public function getOrderItemInfo($orderCodes)
    {
        $orderData = $this->orderModel->getOrderItemInfo($orderCodes);
        return $this->succ($orderData);
    }

    public function getOrderSuperiorInfo($superiorCodes)
    {
        $orderData = $this->orderModel->getOrderSuperiorInfo($superiorCodes);
        return $this->succ($orderData);
    }

    /**
     * 根据订单ID获取订单信息
     */
    public function getOrderInfoByIds($orderIds){
        $orderData = $this->orderModel->getOrderInfoByIds($orderIds);
        return $this->succ($orderData);
    }

    /*
     * 根据订单编号获取退货信息
     * */
    public function getReturnByOrderCode($order_code, $item_id) {
        $orderData = $this->orderModel->getReturnOrderInfo($order_code, $item_id);
        return $this->succ($orderData);
    }

    /**
     * 获取退货申请单信息
     * @param $returnNums
     * @return array
     */
    public function getReturnInfo($returnNums)
    {
        $returnData = $this->orderModel->getReturnInfo($returnNums);
        return $this->succ($returnData);
    }

    /**
     * 获取退款单信息
     * @param $RefundNums
     * @return array
     */
    public function getRefundInfo($RefundNums)
    {
        $refundData = $this->orderModel->getRefundInfo($RefundNums);
        return $this->succ($refundData);
    }

}
