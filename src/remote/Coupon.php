<?php
namespace mia\miagroup\Remote;

use mia\miagroup\Lib\CouponClient;

class Coupon
{

    private $commonStr;
    private $couponClient;
    private $apiClient;
    private $commonParams;

    public function __construct($version='ios_4_7_0')
    {
        $this->commonStr    = $this->_getCommonStr($version);
        $this->couponClient = new CouponClient();
        $this->apiClient    = $this->couponClient->getApiClient();
        $this->commonParams = CouponClient::commonParams('',$this->commonStr);
    }

    /**
     * 查询优惠券剩余数量
     * @param array $batchCodes
     * @return array|null
     */
    public function getRemainCoupon($batchCodes=[])
    {
        $param = [
            'batchCodes' => $batchCodes,
        ];
        
        try{
            $request_startTime = gettimeofday(true);
            $couponParam = new \miasrv\coupon\api\TParamsRemainCoupon($param);
            $res = $this->apiClient->remainCoupon($couponParam, $this->commonParams);
            $request_endTime = gettimeofday(true);

            if (isset($res->code) && $res->code == 0) {
                $result = array();
                foreach ($res->remains as $k => $v) {
                    $result[$v->batchCode] = [
                        'type'   => $v->type,
                        'total'  => $v->total,
                        'remain' => $v->remain,
                    ];
                }
                
                //记录日志
                \F_Ice::$ins->mainApp->logger_remote->info(array(
                    'third_server'  =>  'coupon',
                    'request_param' =>  $param,
                    'response_code' =>  $res->code,
                    'response_data' =>  $result,
                    'response_msg'  =>  '',
                    'resp_time'     =>  number_format(($request_endTime - $request_startTime), 4),
                ));
                
                return $result;
            } else {
                return null;
            }
        }catch (\Exception $e){
            \F_Ice::$ins->mainApp->logger_remote->warn(array(
                'third_server'  =>  'coupon',
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'code'      => $e->getCode(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ));
        }
    }

    /**
     * 获取代金券批次信息
     * @param array $batchCodes
     * @return array|null
     */
    public function getBatchCodeList($batchCodes=[])
    {
        $param = [
            'batchCodes' => $batchCodes,
        ];
    
        $couponParam = new \miasrv\coupon\api\TParamsQueryCouponInfoList($param);
        $res = $this->apiClient->queryCouponInfoList($couponParam, $this->commonParams);
        if (isset($res->code) && $res->code == 0) {
            $result = array();
            foreach ($res->pageList as $k => $v) {
                $result[$v->batchCode] = (array)$v;
            }
            return $result;
        } else {
            return null;
        }
    }

    /**
     * 查询用户绑定的优惠券详细信息
     * @param int $user_id
     * @param array $batch_codes
     * @param int $page_no
     * @param int $page_size
     * @return array|null
     */
    public function queryUserCouponByBatchCode($user_id, $batch_codes, $page_no, $page_size)
    {
        $param = [
            'userId'     => $user_id,
            'batchCodes' => $batch_codes,
            'pageNo'     => $page_no,
            'pageSize'   => $page_size,
        ];

        $coupon_param = new \miasrv\coupon\api\TParamsQueryUserCouponByBatchCode($param);
        $res = $this->apiClient->queryUserCouponByBatchCode($coupon_param, $this->commonParams);
        if (isset($res->code) && $res->code == 0) {
            $item_list = array();
            foreach ($res->couponList as $item) {
                $item_list[] = [
                    'couponCode'    => $item->couponCode,
                    'value'         => $item->value,
                    'minPrice'      => $item->minPrice,
                    'useRang'       => $item->useRang,
                    'startTime'     => $item->startTime,
                    'expireTime'    => $item->expireTime,
                    'bindTime'      => $item->bindTime,
                    'bindUserId'    => $item->bindUserId,
                    'timeValidType' => $item->timeValidType,
                    'validDay'      => $item->validDay,
                    'businessType'  => $item->businessType,
                    'businessId'    => $item->businessId,
                    'isPassword'    => $item->isPassword,
                    'password_code' => $item->password_code,
                    'isUsable'      => $item->isUsable,
                    'unuseableMsg'  => $item->unuseableMsg,
                    'leftUseNum'    => $item->leftUseNum,
                    'type'          => $item->type,
                    'useStartTime'  => $item->useStartTime,
                    'useEndTime'    => $item->useEndTime,
                    'batchCode'     => $item->batchCode,
                ];
            }

            $result = [
                'page_no'          => $res->pageNo,
                'page_size'        => $res->pageSize,
                'total_count'      => $res->totalCount,
                'coupon_info_list' => $item_list,
            ];
            return $result;
        }
        return null;
    }

    /**
     * 根据批次绑定代金券
     * @param int type 类型【签到,神舟,积分兑换,】
     * @param int user_id 用户id
     * @param stringt batch_code 代金券批次编号
     * @return array|bool array(array('batch_codexxx'=>'error inf'))
     */
    public function bindCouponByBatchCode($user_id, $batch_code='')
    {

        $couponBinds = array();
        $tmp = array('batchCode'=>$batch_code, 'count'=>1);
        $couponBinds[] = new \miasrv\coupon\api\TCouponBind($tmp);

        $param = array(
            'tCouponBinds' => $couponBinds,
            'uid'          => $user_id,
            'opUser'       => $user_id
        );
        $coupon_param = new \miasrv\coupon\api\TParamsBindCouponByBatchCode($param);
        $res = $this->apiClient->bindCouponByBatchCode($coupon_param, $this->commonParams);
        if(!empty($res->errorMap)){
            $error_map = array();
            foreach ($res->errorMap as $k=>$v){
                 $error_map[$k]= $v->alert;
            }
            return $error_map;     
        }
        return true;
    }

    private function _getCommonStr($version)
    {
        $version      = strtolower($version);
        $clientOsPath = substr($version, 0, strpos($version, "_"));
        $strVersion   = substr($version, strpos($version, "_")+1, strlen($version));
        $iVersion     = str_replace("_","",$strVersion);
        if (strlen($iVersion) < 3) {
            $iVersion .= "0";
        }
        $iVersion  = intval($iVersion);

        $commonStr = ($iVersion/100).'|'.$clientOsPath;
        return $commonStr;
    }
    
}