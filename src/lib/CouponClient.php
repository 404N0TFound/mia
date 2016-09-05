<?php
namespace mia\miagroup\Lib;


require_once __DIR__ . '/Thrift/lib/Thrift/ClassLoader/ThriftClassLoader.php';
use Thrift\ClassLoader\ThriftClassLoader;

$loader = new ThriftClassLoader();
$loader->registerNamespace('Thrift', __DIR__ . '/Thrift/Lib/');
$loader->registerNamespace('miasrv', __DIR__ . '/');
$loader->registerDefinition('miasrv', __DIR__ . '/');
$loader->register();

use Thrift\Protocol\TCompactProtocol;
use Thrift\Protocol\TMultiplexedProtocol;
use Thrift\Transport\TSocketPool;
use Thrift\Transport\TFramedTransport;
use miasrv\coupon\trade\TCouponTradeServiceClient;
use miasrv\coupon\api\TCouponApiServiceClient;
use miasrv\coupon\ums\TCouponUmsServiceClient;
use miasrv\common\CommonParams;


class CouponClient {
    
    private $socket = array();
    private $transport = array();
    private $protocol = array();
    private $client = array();
    private $service_name = array();
    private $config = false;

    /*
     * $request_id 本次会话唯一标示,传空服务端自动生成返回,建议调用方自行设置,便于查错
     * $appVersion 客户端版本,建议为必传
     */

    public static function commonParams($request_id = '', $appVersion = '')
    {
        return new CommonParams([
            'requestId'        => $request_id,
            'requestTimestamp' => microtime(true),
            'clientVersion'    => '2_0',
            'serviceVersion'   => '2_0',
            'appVersion'       => $appVersion,
        ]);
    }
    public function __construct()
    {
        $this->config = \F_Ice::$ins->workApp->config->get('thrift.address');
    }


    public function getApiClient()
    {
        return $this->_getClient('api');
    }

    private function _getClient($type = 'api')
    {
        if (!isset($this->config[$type])) {
            throw new Exception($type . ' coupon service not defined', 500);
        }
        if (!isset($this->client[$type])) {
            $host = $this->config[$type]['host'];
            $port = $this->config[$type]['port'];
            $recv_timeout = $this->config[$type]['recv_timeout'];
            $service_name = $this->config[$type]['service_name'];

            $this->socket[$type] = new TSocketPool(array($host), array($port), true);
            $this->socket[$type]->setRecvTimeout($recv_timeout);
            $this->service_name[$type] = 'couponsrv@' . $host . ':' . $port;
            $this->transport[$type] = new TFramedTransport($this->socket[$type]);
            $this->protocol[$type] = new TCompactProtocol($this->transport[$type]);
            $this->protocol[$type] = new TMultiplexedProtocol($this->protocol[$type], $service_name);
            $this->transport[$type]->open();
            if ($type == 'api') {
                $this->client[$type] = new TCouponApiServiceClient($this->protocol[$type]);
            } else if ($type == 'ums') {
                $this->client[$type] = new TCouponUmsServiceClient($this->protocol[$type]);
            } else if ($type == 'trade') {
                $this->client[$type] = new TCouponTradeServiceClient($this->protocol[$type]);
            } else {
                throw new Exception($type . ' coupon service not defined', 500);
            }
        }
        return $this->client[$type];
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close()
    {
        foreach ($this->transport as $type => $transport) {
            if ($transport != false) {
                try {
                    $transport->close();
                } catch (Exception $ex) {
                    service_error_log(SERVICE_TYPE_RPC, $this->service_name[$type], -1, 'close service fail,' . $ex->getMessage());
                }
            }
        }
    }

}