<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Push as PushModel;
use mia\miagroup\Model\User as UserModel;
use mia\miagroup\Lib\Rabbitmq;

class Push extends \mia\miagroup\Lib\Service
{
    public $pushModel;
    public $userModel;
    public $rabbitmq;
    
    public function __construct()
    {
        parent::__construct();
        $this->pushModel = new PushModel;
        $this->userModel = new UserModel;
        $this->rabbitmq  = new Rabbitmq;
    }

    /**
     * 推送消息
     * @param   $souceId  来源ID 如subject_id
     * @param   $content  消息内容
     * @param   $toUserId 用户ID
     * @param   $action   来源名称 如subject
     * @return  bool
     */
    public function pushMsg($souceId, $content, $toUserId, $action='subject')
    {
        if (empty($souceId) || empty($content) || empty($toUserId)) {
            return $this->error(500);
        }
        //获取deviceToken
        $deviceToken = $this->userModel->getDeviceTokenByUserId($toUserId);
        if ($deviceToken) {
            $msgInfo = [
                "action"       => $action,
                "action_id"    => $souceId,
                "client_type"  => $deviceToken['client_type'],
                "content"      => $content,
                "device_token" => $deviceToken['device_token'],
            ];
            //添加推送消息
            $msgId = $this->pushModel->addMsg($msgInfo);
            if ($msgId) {
                $message = [
                    "device_token" => $deviceToken['device_token'],
                    "client_type"  => $deviceToken['client_type'],
                    "content"      => $content,
                    "user_id"      => $toUserId,
                    "msg_id"       => $msgId,
                    "action"       => [
                        "name"  => $action,
                        "key"   => "id",
                        "value" => $souceId,
                    ],
                ];
                $res = $this->rabbitmq->send($message);
                return $this->succ($res);
            } else {
                return $this->error(500);
            }
        } else {
            return $this->error(500);
        }
    }
}