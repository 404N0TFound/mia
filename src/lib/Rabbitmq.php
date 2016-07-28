<?php
namespace mia\miagroup\Lib;

class Rabbitmq
{
    public $rabbitmq;

    private $exchange = [];

    public function __construct($cluster='miagroup/default')
    {
        $dsn            = 'rabbitmq://'.$cluster;
        $this->rabbitmq = \F_Ice::$ins->workApp->proxy_resource->get($dsn);
        $this->exchange = \F_Ice::$ins->workApp->config->get('busconf.amqp.amqp_app_push');
        //创建交换机
        $this->rabbitmq->exchangeDeclare($this->exchange['exchange'],$this->exchange['exchange_type'],$this->exchange['durable']);
    }

    /**
     * 发送消息
     *
     * @return void
     * @author 
     **/
    public function send($message)
    {
        if(empty($message) || !isset($message['client_type']) || empty($message['client_type'])) {
            return false;
        }

        $clientType = strtolower($message['client_type']);
        if (!in_array($clientType,['ios','android'])) {
            return false;
        }

        if ($clientType == 'ios') {
            $messageInfo = [
                'text'        => $message['content'],
                'action_info' => [
                    "name"  => $message['action']['name'],
                    "key"   => $message['action']['key'],
                    "value" => $message['action']['value'],
                ],
            ];
        } else {
            $title = isset($message['title']) && !empty($message['title']) ? $message['title'] : '蜜芽宝贝';
            $messageInfo = [
                "push_id"     => $message['msg_id'],
                "ticker"      => '蜜芽宝贝',
                "title"       => $title,
                "text"        => $message['content'],
                "after_open"  => "go_activity",
                "action_info" => [
                    "name"  => $message['action']['name'],
                    "key"   => $message['action']['key'],
                    "value" => $message['action']['value'],
                ],
            ];
        }
        $amqpInfo = [
            "client_type"  => $clientType,
            "user_id"      => $message['user_id'],
            "msg_id"       => $message['msg_id'],
            "device_token" => $message['device_token'],
            "msg_info"     => $messageInfo,
        ];

        $msgInfo = json_encode($amqpInfo);
        $result  = $this->rabbitmq->produce($msgInfo, $this->exchange['exchange'], $this->exchange['routing_key']);
        return $result;
    }


}